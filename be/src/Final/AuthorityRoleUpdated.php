<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\InsufficientAuthorityException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\AdminCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Authority role updated — Final, proof an admin flipped another
 * admin's authority column (Wave 8 doUpdateAuthorityRole).
 *
 *   UpdateAuthorityRoleInput → AuthorityRoleUpdated (Direct, idempotent)
 *
 * AUTHZ ladder — cross-firewall + privilege-escalation guard:
 *
 *   1. No admin session         → UnauthorizedAdminAccessException (403)
 *   2. Unknown caller record    → UnauthorizedAdminAccessException (403)
 *      (session adminId resolved to no admin — stale session)
 *   3. Unknown target loginId   → AdminNotFoundException           (404)
 *   4. caller.authority >=
 *      target.authority         → InsufficientAuthorityException   (403)
 *
 * The "must be strictly higher privilege" rule is the load-bearing
 * AUTHZ extension in Wave 8 — it prevents peer-level admins from
 * silently flipping each other's roles AND it prevents an admin from
 * promoting themselves (their own authority equals their own
 * authority, so the strict inequality refuses self-promotion). See
 * the {@see InsufficientAuthorityException} docblock and the Input
 * class for the full rationale.
 *
 * Mass-assignment safety: only `loginId` (target selector) and
 * `authority` (new value) are accepted. The role-flip path cannot
 * reach name / passwordHash through this Final.
 *
 * Idempotency: when the supplied authority matches the persisted
 * value, the Final short-circuits — no second write, `changed=false`
 * flag set. Mirrors AdminCustomerDeleted / AdminOrderStatusUpdated
 * (Wave 6 / 7).
 */
final readonly class AuthorityRoleUpdated
{
    public string $adminId;
    public string $loginId;
    public int $previousAuthority;
    public int $authority;
    public bool $changed;

    public function __construct(
        #[Input] string $loginId,
        #[Input] int $authority,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] AdminQueryInterface $adminQuery,
        #[Inject] AdminCommandInterface $adminCommand,
    ) {
        $callerId = $adminSession->adminId();
        if ($callerId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $caller = $adminQuery->findById($callerId);
        if ($caller === null) {
            // Session id no longer resolves — treat same as no
            // session, do NOT leak which loginIds exist.
            throw new UnauthorizedAdminAccessException();
        }

        $target = $adminQuery->findByLoginId($loginId);
        if ($target === null) {
            throw new AdminNotFoundException();
        }

        // Critical privilege-escalation guard (load-bearing rule):
        // the caller MUST hold STRICTLY HIGHER privilege than the
        // target's current level. Lower numeric authority = higher
        // privilege, so the check is `caller.authority < target.authority`.
        // Equality covered too — a peer cannot silently demote (or
        // promote) a peer, and self-edits hit this branch because
        // the caller IS the target.
        if ($caller->authority >= $target->authority) {
            throw new InsufficientAuthorityException();
        }

        $previous = $target->authority;

        if ($previous === $authority) {
            // Idempotent replay — same value, no write.
            $this->adminId = $target->adminId;
            $this->loginId = $target->loginId;
            $this->previousAuthority = $previous;
            $this->authority = $authority;
            $this->changed = false;

            return;
        }

        $adminCommand->updateAuthority($target->adminId, $authority);

        $this->adminId = $target->adminId;
        $this->loginId = $target->loginId;
        $this->previousAuthority = $previous;
        $this->authority = $authority;
        $this->changed = true;
    }
}
