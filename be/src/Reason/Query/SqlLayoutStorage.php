<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\LayoutEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed Layout storage — Phase 2b.
 *
 * Mirrors {@see FakeLayoutStorage} against the live EC-CUBE 4.3 schema
 * (`dtb_layout`). The table is grade A — the 3-field LayoutEntity
 * projection (layoutId / layoutName / deviceType) lines up 1:1 with
 * EC-CUBE columns (id / layout_name / device_type_id), with two
 * coercions at the boundary.
 *
 * Interface shape (Wave 9 — LayoutStorageInterface):
 *   list() / getById() / put() only. Layouts have NO create and NO
 *   delete affordance per ALPS (only `goLayoutList` + `doUpdateLayout`),
 *   so there is no SqlLayoutIdGenerator companion and no `remove`
 *   method — the dtb_block_position / dtb_page_layout cascade question
 *   does not arise on this interface (a layout is never deleted, so its
 *   FK referents never need pre-clearing). `put` is the merge-persist
 *   path for the doUpdateLayout transition; it only ever takes an
 *   already-existing layoutId (LayoutUpdated probes `getById` and raises
 *   LayoutNotFoundException before calling `put`), so the INSERT branch
 *   is defensive-only and not exercised by the contract.
 *
 * Scope (Wave 9 — same as LayoutEntity):
 *   The 3-field projection above. EC-CUBE has three more columns on
 *   dtb_layout (`create_date`, `update_date`, `discriminator_type`).
 *   None are part of LayoutStorageInterface. create_date / update_date
 *   are maintained with NOW() (the Doctrine Timestampable behavior
 *   EC-CUBE relies on — same shape SqlPageStorage / SqlBlockStorage
 *   mimic). discriminator_type is 'layout' (the value EC-CUBE writes —
 *   Doctrine single-table inheritance discriminator defaults to the
 *   lowercased class name on Eccube\Entity\Layout). Block-placement
 *   detail (dtb_block_position) and page-association (dtb_page_layout)
 *   are deferred to a later phase — LayoutEntity treats layouts as
 *   opaque named containers, so SqlLayoutStorage never touches either
 *   join table.
 *
 * Coercions:
 *   - `id` is `int unsigned`, LayoutEntity::layoutId is `string`
 *     → cast `(string) (int)` on read, parse with `ctype_digit` on
 *     write. A non-numeric incoming layoutId (e.g. `lo-pc-default` from
 *     the Fake seed) is rejected: getById returns null, put no-ops.
 *     Keeps {@see \MyVendor\BeMart\Be\Final\LayoutUpdated} on its
 *     normal 404 path instead of raising a PDO exception — same
 *     convention as SqlBlockStorage / SqlCategoryStorage /
 *     SqlPageStorage.
 *   - `layout_name` is `varchar(255)` nullable in EC-CUBE but non-null
 *     on LayoutEntity. Hydrator coerces NULL → '' so the projection's
 *     non-null shape is preserved across externally-inserted rows.
 *   - `device_type_id` is `smallint(5) unsigned` nullable, with an FK
 *     to `mtb_device_type` (FK_5A62AA7C4FFA550E). LayoutEntity::deviceType
 *     is a non-null `int` (10=PC, 2=Mobile). mtb_device_type is EMPTY in
 *     the structure-only dump, so any non-NULL value would raise FK
 *     1452. INSERT therefore always writes device_type_id = NULL and
 *     UPDATE never touches the column; the hydrator coerces a NULL
 *     read back to 0 so the projection still has a non-null int.
 *     The contract never deletes/creates a layout, so the only write
 *     path actually exercised (UPDATE via doUpdateLayout) leaves
 *     device_type_id exactly as the seeded fixture set it — the
 *     fixture seeds device_type_id directly (10 / 2) so the read-side
 *     deviceType projection round-trips the EC-CUBE enum unchanged.
 *
 * Upsert convention (`put`):
 *   `put` probes `SELECT 1 WHERE id = ?`; hit → UPDATE (the live path,
 *   driven by doUpdateLayout), miss → INSERT with the explicit id
 *   (defensive — no Layout create affordance exists, so this branch is
 *   not reached by any Final). UPDATE writes layout_name only and
 *   refreshes update_date; device_type_id is left untouched (a layout's
 *   device class is fixed at install time, and LayoutUpdated's merge
 *   carries deviceType through from the current row unchanged).
 *
 * List ordering: `ORDER BY id ASC` — the contract test asserts count,
 * not order. Same shape parity convention as SqlBlockStorage /
 * SqlPageStorage / SqlCategoryStorage.
 *
 * DI is intentionally NOT wired in production (FakeLayoutStorage
 * remains the bound implementation). The SQL impl is exercised via
 * the test-only override in AbstractResourceSqlTestCase.
 */
final class SqlLayoutStorage implements LayoutStorageInterface
{
    private const SELECT_COLUMNS = 'id, layout_name, device_type_id';

    private const DISCRIMINATOR = 'layout';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<LayoutEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_layout '
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
    public function getById(string $layoutId): LayoutEntity|null
    {
        if (! ctype_digit($layoutId)) {
            // Non-numeric ids (e.g. `lo-pc-default` Fake seed,
            // `nonexistent`) can never match an int PK. Surface as miss
            // so LayoutUpdated raises its normal 404 instead of throwing
            // a PDO error.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_layout '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $layoutId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(LayoutEntity $layout): void
    {
        if (! ctype_digit($layout->layoutId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // seeds `lo-` prefixed strings; production must rebind to a
            // numeric-id storage before swapping in this impl.
            return;
        }

        $id = (int) $layout->layoutId;

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_layout WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            // UPDATE is the live path (doUpdateLayout). Only layout_name
            // changes; device_type_id is fixed at install time and the
            // LayoutUpdated merge carries deviceType through unchanged,
            // so it is intentionally left untouched here.
            $sql = 'UPDATE dtb_layout SET '
                . 'layout_name = :layout_name, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':layout_name' => $layout->layoutName,
            ]);

            return;
        }

        // INSERT with explicit id — defensive only (no Layout create
        // affordance exists). device_type_id is NULL: mtb_device_type
        // is empty in the structure-only dump so any non-NULL value
        // would raise FK 1452. discriminator_type is 'layout' (Doctrine
        // single-table inheritance value EC-CUBE writes).
        $sql = 'INSERT INTO dtb_layout '
            . '(id, device_type_id, layout_name, create_date, update_date, '
            . 'discriminator_type) '
            . 'VALUES (:id, NULL, :layout_name, NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':layout_name' => $layout->layoutName,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): LayoutEntity
    {
        return new LayoutEntity(
            layoutId: (string) (int) $row['id'],
            // layout_name is nullable in EC-CUBE but non-null on
            // LayoutEntity — coalesce NULL → '' so the projection shape
            // stays stable across externally-inserted rows.
            layoutName: $row['layout_name'] === null ? '' : (string) $row['layout_name'],
            // device_type_id is nullable; LayoutEntity::deviceType is a
            // non-null int (10=PC, 2=Mobile). Coalesce NULL → 0 so the
            // projection always has an int. Externally-seeded fixture
            // rows carry the real EC-CUBE enum value directly.
            deviceType: $row['device_type_id'] === null ? 0 : (int) $row['device_type_id'],
        );
    }
}
