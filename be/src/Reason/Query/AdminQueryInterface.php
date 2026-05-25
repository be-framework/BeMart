<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;

/**
 * Read-side Admin query — Wave 4 (doAdminLogin / doAdminLogout).
 *
 * Split from a future AdminCommandInterface to keep CQRS boundaries
 * explicit, matching the customer-side {@see CustomerQueryInterface}
 * convention. The admin login flow needs `findByLoginId`; AUTHZ flows
 * (Wave 5: goAdminCustomerList etc.) need `findById` to map a session
 * adminId back to a full Admin record.
 */
interface AdminQueryInterface
{
    /** @return AdminEntity|null  null when no admin has this loginId. */
    public function findByLoginId(string $loginId): AdminEntity|null;

    /** @return AdminEntity|null  null when no admin has this id. */
    public function findById(string $adminId): AdminEntity|null;
}
