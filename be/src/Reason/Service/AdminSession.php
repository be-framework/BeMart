<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Admin session snapshot — the AAA boundary for "which admin is making this request".
 *
 * Admin and customer sessions stay separate because EC-CUBE uses distinct
 * admin/customer firewalls. Domain code reads the adminId value directly for
 * admin-side AUTHZ checks; session storage remains an adapter concern.
 */
abstract readonly class AdminSession
{
    /**
     * @var non-empty-string|null adminId, or null if no admin is logged in
     *
     * @psalm-taint-source session
     */
    public string|null $adminId;

    /** @param non-empty-string|null $adminId */
    public function __construct(string|null $adminId = null)
    {
        $this->adminId = $adminId;
    }
}
