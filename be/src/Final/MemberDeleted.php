<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\InsufficientAuthorityException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Query\AdminCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Member deleted — Final, proof an admin soft-deleted another admin
 * (Wave 8 doDeleteMember).
 *
 *   DeleteMemberInput → MemberDeleted (Direct, idempotent)
 *
 * Soft-delete shape (Wave 6S pattern): the row stays in storage with
 * `work` flipped to 0 (NON_ACTIVE). The grid keeps surfacing it
 * (the admin can re-activate). The login flow filters work=0
 * separately so a soft-deleted admin cannot log in.
 *
 * AUTHZ ladder — cross-firewall + privilege-escalation guard:
 *
 *   1. No admin session       → UnauthorizedAdminAccessException (403)
 *   2. Unknown caller record  → UnauthorizedAdminAccessException (403)
 *      (session adminId resolved to no admin — stale session)
 *   3. Unknown loginId        → AdminNotFoundException           (404)
 *   4. Target is the caller   → InsufficientAuthorityException   (403)
 *   5. Target outranks caller → InsufficientAuthorityException   (403)
 *
 * Step 4 is the ALPS rule "自身は削除不可". Step 5 refuses only a target
 * holding higher privilege than the caller (lower numeric authority);
 * deleting a peer is ordinary shop staffing and hands out nothing the
 * caller does not already hold.
 *
 * The "cannot delete the last system admin" rule (ALPS doc says
 * "最後のシステム管理者も削除不可") is deferred to Phase 2 — it requires
 * a corpus-wide count that this iteration does not wire up.
 *
 * Idempotency: a second delete against an already-inactive admin is a
 * no-op; the Final returns with `alreadyDeleted=true` and no second
 * write happens. Mirrors AdminCustomerDeleted's `alreadyDeleted`
 * discipline (Wave 6S).
 */
final readonly class MemberDeleted
{
    public string $adminId;
    public string $loginId;
    public bool $alreadyDeleted;

    public function __construct(
        #[Input] string $loginId,
        #[Inject] AdminSession $adminSession,
        #[Inject] AdminQueryInterface $adminQuery,
        #[Inject] AdminCommandInterface $adminCommand,
    ) {
        $callerId = $adminSession->adminId;
        if ($callerId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $caller = $adminQuery->item($callerId);
        if ($caller === null) {
            // Session id no longer resolves — treat same as no session.
            throw new UnauthorizedAdminAccessException();
        }

        $target = $adminQuery->byLogin($loginId);
        if ($target === null) {
            throw new AdminNotFoundException();
        }

        if ($target->adminId === $caller->adminId) {
            throw new InsufficientAuthorityException();
        }

        if ($target->authority < $caller->authority) {
            throw new InsufficientAuthorityException();
        }

        // Idempotency: already-inactive → no second write.
        if ($target->work === AdminEntity::WORK_INACTIVE) {
            $this->adminId = $target->adminId;
            $this->loginId = $target->loginId;
            $this->alreadyDeleted = true;

            return;
        }

        $adminCommand->delete($target->adminId);

        $this->adminId = $target->adminId;
        $this->loginId = $target->loginId;
        $this->alreadyDeleted = false;
    }
}
