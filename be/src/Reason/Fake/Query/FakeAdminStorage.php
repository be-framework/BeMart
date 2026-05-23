<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use RuntimeException;

use function array_slice;
use function array_values;
use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function ksort;
use function sprintf;
use function str_contains;

use const JSON_THROW_ON_ERROR;

/**
 * In-memory Admin store — Wave 4 foundation, Wave 8 CRUD extension.
 *
 * Mirrors the customer-side {@see FakeCustomerStorage} shape but indexes
 * by `loginId` because EC-CUBE admins authenticate with a username, not
 * an email.
 *
 * Bound as Singleton in AppModule so AdminCommand writes are visible to
 * FakeAdminQuery within the same request — same convention as customer-
 * side CQRS. Wave 8 introduces put / replace / softDelete / updateAuthority
 * for the admin member management surface (doCreateMember / doUpdateMember
 * / doDeleteMember / doUpdateAuthorityRole).
 *
 * Soft-delete (Wave 8): flips `work` to 0 (NON_ACTIVE) — the row is NOT
 * removed from the map. The grid keeps surfacing it so a system admin
 * can re-activate. The login flow filters work=0 separately.
 *
 * The seed fixture (`var/fake/admins.json`) carries the test admin used
 * by doAdminLogin tests plus a couple of `authority=1` rows used by
 * AUTHZ tests (Wave 5+) and member-management tests (Wave 8).
 */
final class FakeAdminStorage
{
    /** @var array<string, AdminEntity>|null indexed by loginId */
    private array|null $byLoginId = null;

    /**
     * Fetch an Admin by loginId, or null if none. Used by the admin
     * login flow (Wave 4 via FakeAdminQuery).
     */
    public function getByLoginId(string $loginId): AdminEntity|null
    {
        return $this->load()[$loginId] ?? null;
    }

    /**
     * Look up an Admin by their opaque adminId. Used by Wave 5+ AUTHZ
     * flows where the session's adminId is mapped back to a full
     * Admin record. Returns null when no such admin exists.
     */
    public function getById(string $adminId): AdminEntity|null
    {
        foreach ($this->load() as $admin) {
            if ($admin->adminId === $adminId) {
                return $admin;
            }
        }

        return null;
    }

    /**
     * List all admins (active + soft-deleted) sorted by loginId
     * ascending for stable cursor pagination — Wave 8 (goMemberList).
     *
     * @return list<AdminEntity>
     */
    public function listAll(int $limit = 50, int $offset = 0): array
    {
        $rows = $this->load();
        ksort($rows);

        return array_slice(array_values($rows), $offset, $limit);
    }

    /**
     * Substring filter on `name` for the admin grid — Wave 8. Passes
     * over the whole corpus; `nameKeyword === null` (or empty string)
     * returns every row. Case-sensitive `str_contains` is sufficient
     * for v1 — admin names are typically Japanese so case-folding is
     * a no-op.
     *
     * @return list<AdminEntity>
     */
    public function search(string|null $nameKeyword): array
    {
        $rows = array_values($this->load());
        if ($nameKeyword === null || $nameKeyword === '') {
            return $rows;
        }

        $matches = [];
        foreach ($rows as $admin) {
            if (str_contains($admin->name, $nameKeyword)) {
                $matches[] = $admin;
            }
        }

        return $matches;
    }

    /**
     * Insert a new admin — Wave 8 (doCreateMember). The login flow
     * indexes by loginId, so the row key is the new admin's loginId.
     */
    public function put(AdminEntity $admin): void
    {
        $this->load();
        $this->byLoginId[$admin->loginId] = $admin;
    }

    /**
     * Replace an existing admin record handling the case where loginId
     * itself changed (the storage indexes by loginId, so the old key
     * must be removed before the new key goes in). Wave 8
     * (doUpdateMember).
     */
    public function replace(AdminEntity $admin): void
    {
        $rows = $this->load();
        foreach ($rows as $loginId => $existing) {
            if ($existing->adminId === $admin->adminId) {
                unset($rows[$loginId]);

                break;
            }
        }

        $rows[$admin->loginId] = $admin;
        $this->byLoginId = $rows;
    }

    /**
     * Soft-delete: flip `work` to NON_ACTIVE (0) for the row matching
     * `$adminId`. Mirrors EC-CUBE admin deletion which is also
     * physically a flag flip (mtb_work). The row stays in the map so
     * the grid can show "deleted" admins; the login flow filters
     * work=0 separately. Wave 8 (doDeleteMember). Silently does
     * nothing when the adminId is not in the store (the Final's caller
     * has already proven existence via findById, so the no-op branch is
     * unreachable in practice).
     */
    public function softDelete(string $adminId): void
    {
        $rows = $this->load();
        foreach ($rows as $loginId => $admin) {
            if ($admin->adminId !== $adminId) {
                continue;
            }

            if ($admin->work === AdminEntity::WORK_INACTIVE) {
                return;
            }

            $this->byLoginId[$loginId] = new AdminEntity(
                adminId: $admin->adminId,
                loginId: $admin->loginId,
                passwordHash: $admin->passwordHash,
                name: $admin->name,
                authority: $admin->authority,
                work: AdminEntity::WORK_INACTIVE,
            );

            return;
        }
    }

    /**
     * Flip the `authority` column on the row matching `$adminId` —
     * Wave 8 (doUpdateAuthorityRole). Smaller surface than `replace`
     * because the role-flip path only touches the authority field;
     * mass-assignment safety (callers cannot reach name / loginId /
     * passwordHash through this method).
     */
    public function updateAuthority(string $adminId, int $newAuthority): void
    {
        $rows = $this->load();
        foreach ($rows as $loginId => $admin) {
            if ($admin->adminId !== $adminId) {
                continue;
            }

            $this->byLoginId[$loginId] = new AdminEntity(
                adminId: $admin->adminId,
                loginId: $admin->loginId,
                passwordHash: $admin->passwordHash,
                name: $admin->name,
                authority: $newAuthority,
                work: $admin->work,
            );

            return;
        }
    }

    /** @return array<string, AdminEntity> */
    private function load(): array
    {
        if ($this->byLoginId !== null) {
            return $this->byLoginId;
        }

        $path = dirname(__DIR__, 3) . '/var/fake/admins.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake fixture missing: %s', $path));
        }

        /** @var array<string, array{adminId: string, loginId: string, passwordHash: string, name: string, authority: int, work?: int}|string> $rows */
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            throw new RuntimeException(sprintf('Fake fixture must be a JSON object: %s', $path));
        }

        $byLoginId = [];
        foreach ($rows as $key => $row) {
            if ($key === '$comment' || ! is_array($row)) {
                continue;
            }

            $byLoginId[$row['loginId']] = new AdminEntity(
                adminId: $row['adminId'],
                loginId: $row['loginId'],
                passwordHash: $row['passwordHash'],
                name: $row['name'],
                authority: $row['authority'],
                work: $row['work'] ?? AdminEntity::WORK_ACTIVE,
            );
        }

        return $this->byLoginId = $byLoginId;
    }
}
