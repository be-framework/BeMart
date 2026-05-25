<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed Admin read-side — Admin auth Phase B.
 *
 * Mirrors {@see FakeAdminQuery} against the live EC-CUBE 4.3 schema
 * (`dtb_member`). AdminEntity projects six columns (id / login_id /
 * password / name / authority_id / work_id) plus the implicit `work`
 * default (WORK_ACTIVE=1). The remaining dtb_member columns
 * (department / salt / sort_no / two_factor_* / login_date /
 * creator_id / discriminator_type / create_date / update_date) are NOT
 * part of the projection — same scoping choice the Fake side
 * established when the Entity dropped mailAddress in Phase A.
 *
 * Coercions at the boundary:
 *   - `id` is `int unsigned`, AdminEntity::adminId is `string`
 *     → cast `(string) (int)` on read. Non-numeric adminId on findById
 *     can never match an int PK, so return null without prepare
 *     overhead — keeps {@see MemberDeleted} / {@see AuthorityRoleUpdated}
 *     on their normal 404 path instead of raising a PDO error. Same
 *     convention as {@see SqlAddressStorage} / {@see SqlBlockStorage}.
 *   - `name` is column-nullable but AdminEntity::name is non-null
 *     `string` → coerce NULL → '' so externally-inserted rows still
 *     project cleanly (same shape the Fake handles via fixture defaults).
 *   - `authority_id` is column-nullable (FK to mtb_authority which is
 *     EMPTY in the structure-only dump) → coerce NULL → 0 so the
 *     "system admin" sentinel (0) is the safe default for ambiguously-
 *     seeded rows. SqlAdminCommand writes NULL for fixtures and the
 *     same projection step normalises it back to 0.
 *   - `work_id` is column-nullable (FK to mtb_work which is also EMPTY
 *     in the dump) → coerce NULL → WORK_ACTIVE (1). Same rationale: a
 *     row without an explicit work flag defaults to "active" so the
 *     login flow can still admit it. Soft-deleted rows carry an
 *     explicit work_id=0 written by {@see SqlAdminCommand::delete}.
 *
 * List ordering: `ORDER BY login_id ASC` so the admin grid is stable
 * across pagination — matches {@see FakeAdminStorage::listAll} which
 * `ksort`s by loginId. Soft-deleted rows (work_id=0) are intentionally
 * NOT filtered out: the admin grid keeps surfacing them so a system
 * admin can re-activate (the login flow has its own work-rejection
 * filter in {@see \MyVendor\BeMart\Be\Final\AdminAuthenticated} — but
 * actually that filter lives downstream of `findByLoginId`, not in the
 * storage). Same contract as the Fake; the SQL impl preserves it.
 *
 * `search()` runs a case-sensitive LIKE on `name`. Wildcard chars in
 * the keyword are escaped so a user-supplied `%foo%` cannot widen the
 * scan — same defensive pattern as {@see SqlCartCommand::escapeLike}.
 *
 * DI is intentionally NOT wired in production (FakeAdminQuery remains
 * the bound implementation). The SQL impl is exercised via the test-
 * only override in AbstractResourceSqlTestCase.
 */
final class SqlAdminQuery implements AdminQueryInterface
{
    private const SELECT_COLUMNS = 'id, login_id, password, name, authority_id, work_id';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function findByLoginId(string $loginId): AdminEntity|null
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_member '
            . 'WHERE login_id = :login_id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':login_id' => $loginId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function findById(string $adminId): AdminEntity|null
    {
        if (! ctype_digit($adminId)) {
            // Non-numeric ids (e.g. `ad…` hex from FakeAdminIdGenerator)
            // can never match an int PK — surface as miss so the Final
            // raises its normal 404 / 403 instead of a PDO error.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_member '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $adminId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * @return list<AdminEntity>
     */
    #[Override]
    public function listAll(int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_member '
            . 'ORDER BY login_id ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    /**
     * @return list<AdminEntity>
     */
    #[Override]
    public function search(string|null $nameKeyword): array
    {
        if ($nameKeyword === null || $nameKeyword === '') {
            $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_member '
                . 'ORDER BY login_id ASC';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        } else {
            $pattern = '%' . $this->escapeLike($nameKeyword) . '%';
            $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_member '
                . 'WHERE name LIKE :pattern ORDER BY login_id ASC';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':pattern' => $pattern]);
        }

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): AdminEntity
    {
        return new AdminEntity(
            adminId: (string) (int) $row['id'],
            loginId: (string) $row['login_id'],
            passwordHash: (string) $row['password'],
            // `name` is column-nullable but AdminEntity::name is non-null;
            // coerce NULL → '' to preserve the projection shape.
            name: $row['name'] === null ? '' : (string) $row['name'],
            // `authority_id` is column-nullable (mtb_authority empty);
            // coerce NULL → 0 (system-admin sentinel) — same as the Fake
            // which never writes NULL but always carries the int field.
            authority: $row['authority_id'] === null ? 0 : (int) $row['authority_id'],
            // `work_id` is column-nullable (mtb_work empty); coerce
            // NULL → WORK_ACTIVE (1) so absent flag = "active" by
            // default. Soft-delete writes an explicit 0.
            work: $row['work_id'] === null ? AdminEntity::WORK_ACTIVE : (int) $row['work_id'],
        );
    }

    /**
     * Escape `%`, `_`, and `\` so substring keywords cannot smuggle
     * wildcards. Uses `\` as the escape character (MySQL default for
     * LIKE). Same shape as {@see SqlCartCommand::escapeLike}.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
