<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\LoginHistoryEntity;
use Override;
use PDO;

use function date_create_immutable;
use function max;

/**
 * Real PDO-backed admin login-history storage — Phase 2b.
 *
 * Mirrors {@see FakeLoginHistoryStorage} against the live EC-CUBE 4.3
 * schema (`dtb_login_history`). The table is an append + list audit
 * log of every admin login attempt; the BeMart slice carries a
 * 4-field projection (timestamp / loginId / success / clientIp) of
 * the 8-column row.
 *
 * Scope (Wave 8 — same as LoginHistoryEntity):
 *   The 4-field projection lines up with the dtb_login_history columns
 *   as follows, with three coercions at the boundary:
 *     - `create_date`               → timestamp
 *     - `user_name`                 → loginId
 *     - `login_history_status_id`   → success
 *     - `client_ip`                 → clientIp
 *   EC-CUBE's `member_id` (FK to dtb_member) is Phase-2 scope and is
 *   always written NULL from this storage — same convention
 *   {@see SqlNewsStorage} uses for `creator_id` (dtb_member is empty in
 *   the structure-only dump, so a non-NULL member_id raises FK 1452).
 *   `update_date` is set to the same value as `create_date` on append
 *   (an audit row is never mutated, so the two timestamps coincide —
 *   matches the Doctrine Timestampable behavior EC-CUBE relies on for
 *   a never-updated entity).
 *
 * Coercions:
 *   - `login_history_status_id` is `smallint unsigned NOT NULL` with a
 *     FK to `mtb_login_history_status` (FK_6191DD4F9FA62FDD). EC-CUBE's
 *     master ships exactly two rows: 0 = 失敗 (FAILURE), 1 = 成功
 *     (SUCCESS). LoginHistoryEntity::success is a `bool`, so
 *     `(bool) (int)` on read and `(int)` on write. The master table is
 *     EMPTY in the structure-only dump and the column is NOT NULL with
 *     a non-deferrable FK, so a row can never be appended without the
 *     master rows present — {@see SqlFixturesTrait::seedLoginHistoryStatus}
 *     seeds both, same precedent the Admin commit set with
 *     `seedAdminMasters` for the analogous empty-master FK case.
 *   - `create_date` is `datetime` in MySQL local form (no offset);
 *     LoginHistoryEntity::timestamp is an ISO-8601 string with offset
 *     (e.g. `2026-05-19T09:12:34+09:00`). On write, parse the ISO
 *     string and serialise to MySQL `Y-m-d H:i:s` (offset stripped —
 *     same trade-off `sql/diff/entity-vs-eccube.md`'s "Datetime
 *     columns" section documents and {@see SqlNewsStorage} reuses).
 *     On read, the MySQL value comes back as `Y-m-d H:i:s`; we re-emit
 *     it as ISO-8601 with the `+09:00` JST offset so the round-trip
 *     shape is identical to the Fake-backed projection (the Fake seeds
 *     attempts with the JST offset baked in).
 *   - `user_name` / `client_ip` are nullable `longtext`;
 *     LoginHistoryEntity declares both non-nullable strings. On read a
 *     NULL coalesces to the empty string — purely defensive against
 *     externally-inserted rows (EC-CUBE's LoginHistoryListener always
 *     writes a user_name on a failed attempt and the client IP on
 *     both branches; this storage's append always writes both).
 *
 * Append convention:
 *   `append` is a plain INSERT — `id` is `AUTO_INCREMENT` so no id
 *   generator is involved (an audit row has no client-meaningful
 *   handle; LoginHistoryEntity carries no id field, unlike News).
 *   discriminator_type is 'login_history' (the Doctrine single-table
 *   inheritance value EC-CUBE writes).
 *
 * List ordering: `ORDER BY create_date DESC, id DESC` — newest first,
 * matching {@see FakeLoginHistoryStorage::listRecent}'s timestamp-DESC
 * sort. The `id DESC` tiebreaker keeps two attempts in the same second
 * deterministically ordered (the Fake's `strcmp` on identical strings
 * is order-preserving on the seed array, so the SQL sibling adds the
 * id tiebreaker to stay stable). Capped by `$limit` via `LIMIT`.
 *
 * DI is intentionally NOT wired in production (FakeLoginHistoryStorage
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlLoginHistoryStorage implements LoginHistoryStorageInterface
{
    private const SELECT_COLUMNS =
        'create_date, user_name, login_history_status_id, client_ip';

    private const DISCRIMINATOR = 'login_history';

    /** EC-CUBE mtb_login_history_status: 1 = 成功. */
    private const STATUS_SUCCESS = 1;

    /** EC-CUBE mtb_login_history_status: 0 = 失敗. */
    private const STATUS_FAILURE = 0;

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<LoginHistoryEntity> */
    #[Override]
    public function listRecent(int $limit = 50): array
    {
        // Guard a non-positive limit — LIMIT 0 is legal SQL but a
        // negative value is a parse error. The Fake's array_slice
        // treats a non-positive length as "nothing"; match that.
        $cappedLimit = max(0, $limit);

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_login_history '
            . 'ORDER BY create_date DESC, id DESC '
            . 'LIMIT ' . $cappedLimit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    #[Override]
    public function append(LoginHistoryEntity $entry): void
    {
        $timestamp = $this->toMysqlDatetime($entry->timestamp);

        // member_id is NULL (dtb_member is empty in the structure-only
        // dump — any non-NULL value raises FK 1452; resolving loginId →
        // member_id is Phase-2 scope). update_date mirrors create_date
        // (an audit row is never updated). discriminator_type is
        // 'login_history' (the Doctrine single-table inheritance value).
        $sql = 'INSERT INTO dtb_login_history '
            . '(login_history_status_id, member_id, user_name, client_ip, '
            . 'create_date, update_date, discriminator_type) '
            . 'VALUES (:status, NULL, :user_name, :client_ip, '
            . ':create_date, :update_date, :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':status' => $entry->success
                ? self::STATUS_SUCCESS
                : self::STATUS_FAILURE,
            ':user_name' => $entry->loginId,
            ':client_ip' => $entry->clientIp,
            ':create_date' => $timestamp,
            ':update_date' => $timestamp,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): LoginHistoryEntity
    {
        return new LoginHistoryEntity(
            timestamp: $row['create_date'] === null
                ? ''
                : $this->fromMysqlDatetime((string) $row['create_date']),
            loginId: $row['user_name'] === null
                ? ''
                : (string) $row['user_name'],
            success: (int) $row['login_history_status_id'] === self::STATUS_SUCCESS,
            clientIp: $row['client_ip'] === null
                ? ''
                : (string) $row['client_ip'],
        );
    }

    /**
     * Parse an ISO-8601 string (the BeMart on-wire shape) and emit the
     * MySQL `Y-m-d H:i:s` form. The TZ offset is dropped — same
     * convention {@see SqlNewsStorage::toMysqlDatetime} uses. If the
     * input is not a valid ISO timestamp, fall back to "now" rather
     * than write a malformed string.
     */
    private function toMysqlDatetime(string $isoString): string
    {
        $dt = date_create_immutable($isoString);
        if ($dt === false) {
            $dt = date_create_immutable('now');
            if ($dt === false) {
                return '1970-01-01 00:00:00';
            }
        }

        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Convert a MySQL `Y-m-d H:i:s` datetime back to the ISO-8601 shape
     * the Fake-backed projection emits (with the JST offset baked in).
     * The Fake seeds attempts with `+09:00`; matching the offset keeps
     * the round-trip identical across both backends.
     */
    private function fromMysqlDatetime(string $mysqlDatetime): string
    {
        $dt = date_create_immutable($mysqlDatetime . ' Asia/Tokyo');
        if ($dt === false) {
            return $mysqlDatetime;
        }

        return $dt->format('Y-m-d\TH:i:sP');
    }
}
