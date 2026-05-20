<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use Override;
use PDO;

use function ctype_digit;

/**
 * Real PDO-backed Admin write-side — Admin auth Phase B.
 *
 * Mirrors {@see FakeAdminCommand} against the live EC-CUBE 4.3 schema
 * (`dtb_member`). The four mutators (create / update / delete /
 * updateAuthority) operate on the same 6-column projection
 * {@see SqlAdminQuery} reads back, with the remaining dtb_member
 * columns defaulted to safe values on INSERT:
 *
 *   - `sort_no` (NOT NULL smallint, no DEFAULT) → 0. The 4.3 admin UI
 *     uses sort_no to order the member grid; the BeMart admin slice
 *     does not expose it so we write a stable sentinel.
 *   - `salt` → NULL. EC-CUBE 4.x bcrypt stores the salt inside the
 *     password hash itself, so this column is vestigial.
 *   - `department` → NULL. Out of scope for the BeMart admin slice.
 *   - `two_factor_auth_key` → NULL, `two_factor_auth_enabled` → 0.
 *     Two-factor admin auth is Phase 2 scope.
 *   - `login_date` → NULL. Updated on successful login by a future
 *     login-history Final, not by this CRUD path.
 *   - `creator_id` → NULL. The schema makes it a self-FK with no
 *     ON DELETE / ON UPDATE cascade; the BeMart Final pipeline does
 *     not yet track who created whom. NULL is safe (FK is nullable).
 *   - `authority_id` → caller-supplied AdminEntity::authority,
 *     written verbatim. The column is a FK to mtb_authority which
 *     is EMPTY in the structure-only dump, so the SQL test base
 *     seeds the two EC-CUBE canonical rows (0 = system admin,
 *     1 = shop owner) via {@see \MyVendor\BeMart\Be\Tests\Sql\SqlFixturesTrait::seedAdminMasters}
 *     before any write. Production runs against a fully-installed
 *     EC-CUBE database where mtb_authority is already populated.
 *   - `work_id` → caller-supplied AdminEntity::work, written
 *     verbatim. FK to mtb_work (also EMPTY in the dump, also seeded
 *     by seedAdminMasters: 0 = NON_ACTIVE, 1 = ACTIVE).
 *     {@see delete} writes an explicit 0 (NON_ACTIVE) — soft-delete
 *     MUST be observable on read, and mtb_work id=0 exists once the
 *     masters are seeded.
 *   - `discriminator_type` → 'member' (Doctrine single-table
 *     discriminator value EC-CUBE writes for Eccube\Entity\Member —
 *     verified via tools/ec-cube-source/var/cache/install/doctrine/orm
 *     default_metadata.php which carries 'discr' = ['member' => ...]).
 *   - `create_date` / `update_date` → NOW() on insert; only
 *     `update_date` on UPDATE / soft-delete / authority flip
 *     (matches the Doctrine Timestampable behavior EC-CUBE relies on).
 *
 * Pre-allocated id discipline (`create`):
 *   adminId is pre-allocated by {@see \MyVendor\BeMart\Be\Reason\Service\SqlAdminIdGenerator}
 *   before `create` is called (MemberCreated assigns
 *   `$entity->adminId` from the generator output, so the command
 *   receives an id-bearing entity). Non-numeric ids are rejected as a
 *   silent no-op — the Fake generator emits `ad…` hex which would
 *   otherwise collide with the int PK; production must rebind to the
 *   SQL generator before swapping in this storage. Same convention as
 *   {@see SqlAddressStorage} / {@see SqlBlockStorage} / etc.
 *
 * Soft-delete semantics (`delete`):
 *   Flips `work_id` to 0 (NON_ACTIVE). The row stays in the table for
 *   audit / FK integrity (dtb_login_history.member_id FK references
 *   it). The grid keeps surfacing it so a system admin can re-
 *   activate. The login flow rejects work=0 separately. Same shape as
 *   {@see FakeAdminCommand::delete} → {@see FakeAdminStorage::softDelete}.
 *
 * `updateAuthority` is a single-column UPDATE — narrow surface so the
 * role-flip path cannot reach unrelated fields (mass-assignment
 * safety, Pilot 5 F-2 lesson, called out in the interface docblock).
 *
 * DI is intentionally NOT wired in production (FakeAdminCommand
 * remains the bound implementation). The SQL impl is exercised via
 * the test-only override in AbstractResourceSqlTestCase.
 */
final class SqlAdminCommand implements AdminCommandInterface
{
    private const DISCRIMINATOR = 'member';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function create(AdminEntity $admin): void
    {
        if (! ctype_digit($admin->adminId)) {
            // Defensive: a non-numeric id we cannot persist with an
            // explicit PK. The Fake generator emits `ad…` hex;
            // production must rebind to SqlAdminIdGenerator before
            // swapping in this storage.
            return;
        }

        $sql = 'INSERT INTO dtb_member '
            . '(id, creator_id, work_id, authority_id, name, department, '
            . 'login_id, password, salt, sort_no, two_factor_auth_key, '
            . 'two_factor_auth_enabled, create_date, update_date, '
            . 'login_date, discriminator_type) '
            . 'VALUES (:id, NULL, :work_id, :authority_id, :name, NULL, '
            . ':login_id, :password, NULL, 0, NULL, '
            . '0, NOW(), NOW(), NULL, :discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => (int) $admin->adminId,
            // work / authority written verbatim — the FK targets
            // mtb_work / mtb_authority are seeded with the EC-CUBE
            // canonical rows (test base) or already populated
            // (production install).
            ':work_id' => $admin->work,
            ':authority_id' => $admin->authority,
            ':name' => $admin->name,
            ':login_id' => $admin->loginId,
            ':password' => $admin->passwordHash,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function update(AdminEntity $admin): void
    {
        if (! ctype_digit($admin->adminId)) {
            return;
        }

        $sql = 'UPDATE dtb_member SET '
            . 'login_id = :login_id, '
            . 'password = :password, '
            . 'name = :name, '
            . 'authority_id = :authority_id, '
            . 'work_id = :work_id, '
            . 'update_date = NOW() '
            . 'WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => (int) $admin->adminId,
            ':login_id' => $admin->loginId,
            ':password' => $admin->passwordHash,
            ':name' => $admin->name,
            ':authority_id' => $admin->authority,
            ':work_id' => $admin->work,
        ]);
    }

    #[Override]
    public function delete(string $adminId): void
    {
        if (! ctype_digit($adminId)) {
            // Silent no-op on a non-numeric id — same shape as the
            // Fake which returns without raising when the id is missing.
            return;
        }

        // Soft-delete: flip work_id to 0 (NON_ACTIVE). The row stays
        // in the table for audit / FK integrity. Explicit 0 (not NULL)
        // because hydrate() coerces NULL → WORK_ACTIVE so a NULL would
        // make the deletion invisible on subsequent reads.
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_member SET work_id = 0, update_date = NOW() WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $adminId]);
    }

    #[Override]
    public function updateAuthority(string $adminId, int $newAuthority): void
    {
        if (! ctype_digit($adminId)) {
            return;
        }

        // Single-column UPDATE — the narrow surface is the whole point
        // of having this method vs `update()`. mass-assignment safety:
        // the role-flip path cannot reach unrelated fields. authority
        // written verbatim (FK target mtb_authority is seeded / already
        // populated).
        $stmt = $this->pdo->prepare(
            'UPDATE dtb_member SET authority_id = :authority_id, '
            . 'update_date = NOW() WHERE id = :id',
        );
        $stmt->execute([
            ':id' => (int) $adminId,
            ':authority_id' => $newAuthority,
        ]);
    }
}
