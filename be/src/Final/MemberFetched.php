<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Member fetched — Final, admin detail projection for one admin
 * member (Wave 8 goMember).
 *
 *   GetMemberInput → MemberFetched  (Direct, safe read)
 *
 * AUTHZ — cross-firewall ladder (same shape as goCustomer / Wave 5N):
 *
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown loginId      → AdminNotFoundException            (404)
 *
 * Anti-enumeration: AUTHZ runs first so an admin-anonymous client
 * learns nothing about which loginIds resolve.
 *
 * Public surface — SAFE projection of AdminEntity. The full entity
 * carries `passwordHash` which MUST NOT leak into the HTTP body; this
 * Final mirrors AdminAuthenticated's discipline (no hash escape).
 */
final readonly class MemberFetched
{
    public string $adminId;
    public string $loginId;
    public string $name;
    public int $authority;
    public int $work;
    public int $sortNo;

    public function __construct(
        #[Input] string $loginId,
        #[Inject] AdminSession $adminSession,
        #[Inject] AdminQueryInterface $adminQuery,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $admin = $adminQuery->byLogin($loginId);
        if ($admin === null) {
            throw new AdminNotFoundException();
        }

        $this->adminId = $admin->adminId;
        $this->loginId = $admin->loginId;
        $this->name = $admin->name;
        $this->authority = $admin->authority;
        $this->work = $admin->work;
        $this->sortNo = $admin->sortNo;
    }
}
