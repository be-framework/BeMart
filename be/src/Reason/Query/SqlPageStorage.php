<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PageEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed Page storage — Phase 2b.
 *
 * Mirrors {@see FakePageStorage} against the live EC-CUBE 4.3 schema
 * (`dtb_page`). The table is grade A — the 5-field PageEntity projection
 * (pageId / pageName / pageUrl / pageFileName / pageEditType) lines up
 * 1:1 with EC-CUBE columns (id / page_name / url / file_name /
 * edit_type), with three coercions at the boundary.
 *
 * Scope (Wave 9ζ — same as PageEntity):
 *   The 5-field projection above. EC-CUBE has eight more columns on
 *   dtb_page (master_page_id, author, description, keyword, meta_robots,
 *   meta_tags, create_date, update_date) and a sibling join table
 *   `dtb_page_layout`. None are part of PageStorageInterface — the
 *   admin flat-list / CRUD UX only edits the 5 projected fields, and
 *   layout composition is Phase 2 scope. master_page_id is always
 *   written NULL (the master_page chain is EC-CUBE's
 *   default-template-override mechanism — out of scope for the BeMart
 *   admin slice); author / description / keyword / meta_* are always
 *   written NULL (no UI for them); create_date / update_date are
 *   maintained with NOW() (same Doctrine Timestampable behavior
 *   {@see SqlNewsStorage} mimics). No dtb_page_layout row is written
 *   on INSERT — the join table is composite-PK (page_id, layout_id) so
 *   absence simply means "page is not placed on any layout", which is
 *   semantically valid: EC-CUBE's admin allows page existence without
 *   layout placement. On DELETE we issue a defensive
 *   `DELETE FROM dtb_page_layout WHERE page_id = ?` first to keep the
 *   FK (FK_F2799941C4663E4) from blocking the page deletion if a row
 *   was seeded externally.
 *
 * Coercions:
 *   - `id` is `int unsigned`, PageEntity::pageId is `string`
 *     → cast `(string) (int)` on read, parse with `ctype_digit` on
 *     write. A non-numeric incoming pageId (e.g. `pg-homepage` from the
 *     Fake seed or hex from {@see FakePageIdGenerator}) is rejected:
 *     getById returns null, put no-ops, remove no-ops. Keeps
 *     {@see PageDeleted} / {@see PageUpdated} / {@see AdminPageFetched}
 *     on their normal 404 path instead of raising a PDO exception —
 *     same shape the Fake exhibits when the id is absent. Same
 *     convention as {@see SqlTagStorage} / {@see SqlNewsStorage} /
 *     {@see SqlTaxRuleStorage}.
 *   - `page_name` / `file_name` are nullable in EC-CUBE but non-null on
 *     PageEntity. Hydrator coerces NULL → '' so the projection's
 *     non-null shape is preserved across externally-inserted rows.
 *   - `edit_type` is `smallint(5) unsigned NOT NULL DEFAULT 1`,
 *     PageEntity::pageEditType is `int` → direct cast.
 *
 * Upsert convention (`put`):
 *   pageId is pre-allocated by {@see SqlPageIdGenerator} before `put`
 *   is called (PageCreated assigns `$entity->pageId` from the generator
 *   output, so the storage receives an id-bearing entity).
 *   `put` probes `SELECT 1 WHERE id = ?`; hit → UPDATE, miss → INSERT
 *   with the explicit id. discriminator_type is 'page' (the value
 *   EC-CUBE writes — Doctrine single-table inheritance discriminator).
 *
 *   ALPS defines `doUpdatePage` (PageUpdated Final flows merges + put
 *   on the same id), so the UPDATE branch is actively exercised, same
 *   as the News / BaseInfo flows.
 *
 * Timestamps: NOW() on insert for both `create_date` and `update_date`;
 * NOW() on `update_date` only for updates (matches the Doctrine
 * Timestampable behavior EC-CUBE relies on). No timezone column on
 * dtb_page — datetime is interpreted server-local same as
 * SqlNewsStorage.
 *
 * List ordering: `ORDER BY id ASC` — the contract test does not assert
 * order, only count and presence. Same shape parity convention as
 * SqlNewsStorage / SqlTagStorage / SqlTaxRuleStorage / SqlAddressStorage.
 *
 * DI is intentionally NOT wired in production (FakePageStorage remains
 * the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlPageStorage implements PageStorageInterface
{
    private const SELECT_COLUMNS = 'id, page_name, url, file_name, edit_type';

    private const DISCRIMINATOR = 'page';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<PageEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_page '
            . 'ORDER BY id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    #[Override]
    public function getById(string $pageId): PageEntity|null
    {
        if (! ctype_digit($pageId)) {
            // Non-numeric ids (e.g. `pg-homepage` Fake seed, hex from
            // FakePageIdGenerator) can never match an int PK. Surface
            // as miss so PageDeleted / PageUpdated / AdminPageFetched
            // raise their normal 404 instead of throwing a PDO error.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_page '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $pageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(PageEntity $page): void
    {
        if (! ctype_digit($page->pageId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits a `pg-` prefixed string; production must
            // rebind to SqlPageIdGenerator before swapping in this
            // storage.
            return;
        }

        $id = (int) $page->pageId;

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_page WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $sql = 'UPDATE dtb_page SET '
                . 'page_name = :page_name, '
                . 'url = :url, '
                . 'file_name = :file_name, '
                . 'edit_type = :edit_type, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':page_name' => $page->pageName,
                ':url' => $page->pageUrl,
                ':file_name' => $page->pageFileName,
                ':edit_type' => $page->pageEditType,
            ]);

            return;
        }

        // INSERT with explicit id. master_page_id / author / description /
        // keyword / meta_robots / meta_tags are all NULL — Wave 9 admin
        // slice exposes only the 5-field projection, and the columns
        // are nullable in the schema. discriminator_type is 'page' (the
        // Doctrine single-table inheritance value EC-CUBE writes).
        $sql = 'INSERT INTO dtb_page '
            . '(id, master_page_id, page_name, url, file_name, edit_type, '
            . 'author, description, keyword, create_date, update_date, '
            . 'meta_robots, meta_tags, discriminator_type) '
            . 'VALUES (:id, NULL, :page_name, :url, :file_name, :edit_type, '
            . 'NULL, NULL, NULL, NOW(), NOW(), NULL, NULL, :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':page_name' => $page->pageName,
            ':url' => $page->pageUrl,
            ':file_name' => $page->pageFileName,
            ':edit_type' => $page->pageEditType,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $pageId): void
    {
        if (! ctype_digit($pageId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which `unset()`s a missing key without raising.
            return;
        }

        $id = (int) $pageId;

        // Drop any dtb_page_layout placements for this page first so
        // FK_F2799941C4663E4 (page_layout.page_id → page.id) does not
        // block the row deletion. The Wave 9 admin slice never INSERTs
        // a placement row, but an externally-seeded one would otherwise
        // raise FK 1451. Idempotent — zero rows is fine.
        $stmt = $this->pdo->prepare(
            'DELETE FROM dtb_page_layout WHERE page_id = :id',
        );
        $stmt->execute([':id' => $id]);

        $stmt = $this->pdo->prepare('DELETE FROM dtb_page WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): PageEntity
    {
        return new PageEntity(
            pageId: (string) (int) $row['id'],
            // page_name / file_name are nullable in EC-CUBE but
            // non-null on PageEntity — coalesce NULL → '' so the
            // projection shape stays stable across externally-inserted
            // rows.
            pageName: $row['page_name'] === null ? '' : (string) $row['page_name'],
            pageUrl: (string) $row['url'],
            pageFileName: $row['file_name'] === null ? '' : (string) $row['file_name'],
            pageEditType: (int) $row['edit_type'],
        );
    }
}
