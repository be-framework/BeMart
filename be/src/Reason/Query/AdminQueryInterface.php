<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Query\Factory\AdminFactory;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Read-side Admin query — Wave 4 (doAdminLogin / doAdminLogout).
 *
 * Split from the AdminCommand surface to keep CQRS boundaries explicit,
 * matching the customer-side {@see CustomerQueryInterface} convention.
 * The admin login flow needs `findByLoginId`; AUTHZ flows (Wave 5+) need
 * `findById` to map a session adminId back to a full Admin record.
 *
 * Wave 8 (admin member CRUD) extends the contract with list/search
 * primitives so the admin grid + filter form can drive over the same
 * Storage that backs `findByLoginId` / `findById`. Soft-deleted admins
 * (work=0) MUST stay visible to listAll/search/findById — the grid
 * surfaces them so a system admin can re-activate. The login flow has
 * its own filter (it rejects work=0 before issuing a session).
 */
interface AdminQueryInterface
{
    /** @return AdminEntity|null  null when no admin has this loginId. */
    #[DbQuery('admin_find_by_login', factory: AdminFactory::class)]
    public function findByLoginId(string $loginId): AdminEntity|null;

    /** @return AdminEntity|null  null when no admin has this id. */
    #[DbQuery('admin_find_by_id', factory: AdminFactory::class)]
    public function findById(string $adminId): AdminEntity|null;

    /**
     * List all admins (incl. soft-deleted) for the admin grid —
     * Wave 8 (goMemberList). Sorted by `loginId` ascending for stable
     * cursor pagination; the storage walks a bounded in-memory map so
     * stable iteration order is the obvious convention.
     *
     * @return list<AdminEntity>
     */
    #[DbQuery('admin_list', factory: AdminFactory::class)]
    public function listAll(int $limit = 50, int $offset = 0): array;

    /**
     * Substring filter on `name` for the admin grid filter form —
     * Wave 8 (goMemberList). Pass null to disable the filter. Returns
     * an empty list when nothing matches.
     *
     * @return list<AdminEntity>
     */
    #[DbQuery('admin_search', factory: AdminFactory::class)]
    public function search(string|null $nameKeyword): array;
}
