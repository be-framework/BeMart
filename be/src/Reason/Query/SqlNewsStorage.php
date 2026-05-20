<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use Override;
use PDO;

use function ctype_digit;
use function date_create_immutable;

/**
 * Real PDO-backed News storage — Phase 2b.
 *
 * Mirrors {@see FakeNewsStorage} against the live EC-CUBE 4.3 schema
 * (`dtb_news`). The table is grade A — the BeMart-modelled columns
 * (title / description / url / publish_date / link_method) line up 1:1
 * with NewsEntity, with three coercions at the boundary.
 *
 * Scope (Wave 9ζ — same as NewsEntity):
 *   The 6-field projection: id / title / description / url /
 *   publish_date / link_method. EC-CUBE's `creator_id` (FK to
 *   dtb_member) and `visible` are Phase-2 scope and always written
 *   as NULL / 1 from this storage — same convention
 *   {@see SqlTaxRuleStorage} uses for the empty `mtb_*` master tables
 *   (dtb_member is empty in the structure-only dump, so creator_id
 *   stays NULL to avoid FK 1452; visible defaults to 1 so the row is
 *   shown by default — matches EC-CUBE's installer convention).
 *
 * Coercions:
 *   - `id` is `int unsigned`, NewsEntity::newsId is `string`
 *     → cast `(string) (int)` on read, parse with `ctype_digit` on
 *     write. A non-numeric incoming newsId (e.g. `nw-welcome` from the
 *     Fake seed or hex from {@see FakeNewsIdGenerator}) is rejected:
 *     getById returns null, put no-ops, remove no-ops. Keeps
 *     {@see NewsDeleted} / {@see AdminNewsFetched} on their normal 404
 *     path instead of raising a PDO exception, same shape the Fake
 *     exhibits when the id is absent. Same convention as
 *     {@see SqlTagStorage} / {@see SqlTaxRuleStorage}.
 *   - `publish_date` is nullable `datetime` in MySQL local form;
 *     NewsEntity::publishDate is a NOT-NULL ISO-8601 string with offset
 *     (e.g. `2026-05-01T00:00:00+09:00`). On write, parse the ISO
 *     string and serialise to MySQL `Y-m-d H:i:s` (offset stripped —
 *     same trade-off documented in `sql/diff/entity-vs-eccube.md`'s
 *     "Datetime columns" section and reused from SqlTaxRuleStorage).
 *     On read, the MySQL value comes back as `Y-m-d H:i:s`; we re-emit
 *     it as an ISO-8601 string with the `+09:00` JST offset so the
 *     round-trip shape is identical to the Fake-backed projection (the
 *     contract test seeds news with the JST offset baked in). If the
 *     row's publish_date is NULL (allowed by the schema), the hydrator
 *     falls back to the empty string — NewsEntity declares it
 *     non-nullable, and the contract test always sets one, so this
 *     branch is purely defensive against externally-inserted rows.
 *   - `link_method` is `tinyint(1) NOT NULL DEFAULT 0`,
 *     NewsEntity::linkMethod is `bool` → cast `(bool) (int)` on read,
 *     `(int)` on write.
 *
 * Upsert convention (`put`):
 *   newsId is pre-allocated by {@see SqlNewsIdGenerator} before `put`
 *   is called (NewsCreated assigns `$entity->newsId` from the generator
 *   output, so the storage receives an id-bearing entity).
 *   `put` probes `SELECT 1 WHERE id = ?`; hit → UPDATE, miss → INSERT
 *   with the explicit id. discriminator_type is 'news' (the value
 *   EC-CUBE writes — Doctrine single-table inheritance discriminator).
 *
 *   Unlike TaxRule, ALPS DOES define `doUpdateNews` (NewsUpdated Final
 *   flows merges + put on the same id), so the UPDATE branch is
 *   actively exercised, not just defensive.
 *
 * Timestamps: NOW() on insert for both `create_date` and `update_date`;
 * NOW() on `update_date` only for updates (matches the Doctrine
 * Timestampable behavior EC-CUBE relies on).
 *
 * List ordering: `ORDER BY id ASC` — the contract test does not assert
 * order, only count. The Fake-backed projection sorts (publishDate
 * desc, newsId asc); the SQL sibling stays on the simpler id ASC for
 * shape parity with SqlTaxRuleStorage / SqlTagStorage / SqlAddressStorage.
 *
 * DI is intentionally NOT wired in production (FakeNewsStorage remains
 * the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlNewsStorage implements NewsStorageInterface
{
    private const SELECT_COLUMNS = 'id, title, description, url, publish_date, link_method';

    private const DISCRIMINATOR = 'news';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<NewsEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_news '
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
    public function getById(string $newsId): NewsEntity|null
    {
        if (! ctype_digit($newsId)) {
            // Non-numeric ids (e.g. `nw-welcome` Fake seed, hex from
            // FakeNewsIdGenerator) can never match an int PK. Surface
            // as miss so NewsDeleted / AdminNewsFetched raise their
            // normal 404 instead of throwing a PDO exception.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_news '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $newsId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(NewsEntity $news): void
    {
        if (! ctype_digit($news->newsId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits an `nw-` prefixed string; production must
            // rebind to SqlNewsIdGenerator before swapping in this
            // storage.
            return;
        }

        $id = (int) $news->newsId;
        $publishDateMysql = $this->toMysqlDatetime($news->publishDate);

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_news WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $sql = 'UPDATE dtb_news SET '
                . 'title = :title, '
                . 'description = :description, '
                . 'url = :url, '
                . 'publish_date = :publish_date, '
                . 'link_method = :link_method, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':title' => $news->newsTitle,
                ':description' => $news->newsDescription,
                ':url' => $news->newsUrl,
                ':publish_date' => $publishDateMysql,
                ':link_method' => (int) $news->linkMethod,
            ]);

            return;
        }

        // INSERT with explicit id. creator_id is NULL (dtb_member is
        // empty in the structure-only dump — any non-NULL value raises
        // FK 1452). visible defaults to 1 so the row is shown by
        // default, matching EC-CUBE's installer convention.
        // discriminator_type is 'news' (the Doctrine single-table
        // inheritance value EC-CUBE writes).
        $sql = 'INSERT INTO dtb_news '
            . '(id, creator_id, title, description, url, publish_date, '
            . 'link_method, visible, create_date, update_date, '
            . 'discriminator_type) '
            . 'VALUES (:id, NULL, :title, :description, :url, :publish_date, '
            . ':link_method, 1, NOW(), NOW(), :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':title' => $news->newsTitle,
            ':description' => $news->newsDescription,
            ':url' => $news->newsUrl,
            ':publish_date' => $publishDateMysql,
            ':link_method' => (int) $news->linkMethod,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $newsId): void
    {
        if (! ctype_digit($newsId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which `unset()`s a missing key without raising.
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM dtb_news WHERE id = :id');
        $stmt->execute([':id' => (int) $newsId]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): NewsEntity
    {
        return new NewsEntity(
            newsId: (string) (int) $row['id'],
            newsTitle: (string) $row['title'],
            newsDescription: $row['description'] === null
                ? null
                : (string) $row['description'],
            newsUrl: $row['url'] === null ? null : (string) $row['url'],
            publishDate: $row['publish_date'] === null
                ? ''
                : $this->fromMysqlDatetime((string) $row['publish_date']),
            linkMethod: (bool) (int) $row['link_method'],
        );
    }

    /**
     * Parse an ISO-8601 string (the BeMart on-wire shape) and emit the
     * MySQL `Y-m-d H:i:s` form. The TZ offset is dropped — same
     * convention `sql/diff/entity-vs-eccube.md`'s "Datetime columns"
     * section calls out for every BeMart→EC-CUBE write. If the input
     * is not a valid ISO timestamp, fall back to "now" rather than
     * write a malformed string (CreateNewsInput / UpdateNewsInput pass
     * the value through unchanged so this is purely defensive).
     */
    private function toMysqlDatetime(string $isoString): string
    {
        $dt = date_create_immutable($isoString);
        if ($dt === false) {
            $dt = date_create_immutable('now');
            if ($dt === false) {
                // date_create_immutable('now') is impossible to fail
                // but the static analyser cannot prove that. Hand-roll
                // the bare format string as a last resort.
                return '1970-01-01 00:00:00';
            }
        }

        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Convert a MySQL `Y-m-d H:i:s` datetime back to the ISO-8601 shape
     * the Fake-backed projection emits (with the JST offset baked in).
     * The contract test seeds news posts with `+09:00`; matching the
     * offset keeps the round-trip identical across both backends.
     */
    private function fromMysqlDatetime(string $mysqlDatetime): string
    {
        // Interpret the stored value as Asia/Tokyo so the offset on the
        // emitted ISO string matches the JST convention the Fake
        // backend uses.
        $dt = date_create_immutable($mysqlDatetime . ' Asia/Tokyo');
        if ($dt === false) {
            return $mysqlDatetime;
        }

        return $dt->format('Y-m-d\TH:i:sP');
    }
}
