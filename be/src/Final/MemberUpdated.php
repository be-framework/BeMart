<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Query\AdminCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Member updated — Final, proof an admin's profile fields were edited
 * in place by another admin (Wave 8 doUpdateMember).
 *
 *   UpdateMemberInput → MemberUpdated  (Direct, idempotent)
 *
 * AUTHZ — cross-firewall ladder:
 *
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown loginId      → AdminNotFoundException            (404)
 *
 * Merge semantics: only `name` is part of this transition's scope.
 * Null leaves the existing value untouched. loginId / authority / work
 * / passwordHash are preserved verbatim — those go through their own
 * dedicated transitions (doUpdateAuthorityRole / doDeleteMember /
 * future password-change). EC-CUBE 4.3 dtb_member has no email column,
 * so no mailAddress field is part of the projection.
 *
 * Mass-assignment safety (Pilot 5 F-2 lesson): the target is selected
 * by loginId (a non-secret identifier) but the path only touches the
 * editable fields; there is no way to reach passwordHash or work
 * through this Final.
 */
final readonly class MemberUpdated
{
    public string $adminId;
    public string $loginId;
    public string $name;
    public int $authority;
    public int $work;
    public int $sortNo;

    public function __construct(
        #[Input] string $loginId,
        #[Input] string|null $name,
        #[Inject] AdminSession $adminSession,
        #[Inject] AdminQueryInterface $adminQuery,
        #[Inject] AdminCommandInterface $adminCommand,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $adminQuery->byLogin($loginId);
        if ($current === null) {
            throw new AdminNotFoundException();
        }

        $merged = new AdminEntity(
            adminId: $current->adminId,
            loginId: $current->loginId,
            passwordHash: $current->passwordHash,
            name: $name ?? $current->name,
            authority: $current->authority,
            work: $current->work,
            sortNo: $current->sortNo,
        );

        $adminCommand->update($merged);

        $this->adminId = $merged->adminId;
        $this->loginId = $merged->loginId;
        $this->name = $merged->name;
        $this->authority = $merged->authority;
        $this->work = $merged->work;
        $this->sortNo = $merged->sortNo;
    }
}
