<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\InsufficientAuthorityException;
use MyVendor\BeMart\Be\Exception\LoginIdAlreadyTakenException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberCreated;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Provider\AdminIdProvider;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;
use SensitiveParameter;

/**
 * The admin-member-being-created moment — Wave 8 (doCreateMember).
 *
 * Multi-Reason Being mirroring Wave 5O {@see AdminCustomerCreating},
 * with the admin AUTHZ ladder as the first checks:
 *
 *   0. AdminSession            — fail-fast if no admin session
 *   1. AdminQueryInterface     — caller record + privilege guard, then
 *                                fail-fast on duplicate loginId
 *   2. AdminIdProvider         — opaque 32-char hex id
 *   3. PasswordHasherInterface — bcrypt hash of plaintext password
 *
 * Existence of this object proves all of them succeeded. The downstream
 * Final ({@see MemberCreated}) only has to persist an AdminEntity
 * built from this public surface.
 *
 * `work` is fixed to 1 (ACTIVE) — newly-created admins can log in
 * immediately. Soft-delete (work=0) is a downstream state flip via
 * doDeleteMember.
 *
 * Password handling: the plaintext `$password` is consumed inside the
 * constructor (`#[SensitiveParameter]`) and intentionally NOT promoted
 * to a public property — same discipline as AdminCustomerCreating.
 *
 * AUTHZ rationale: admins are managed by other admins. A logged-in
 * customer is NOT logged-in-as-admin and must not reach this code
 * path. The checks are at Being-time so the resource layer can map the
 * resulting exceptions to HTTP 403.
 *
 * AUTHZ ladder:
 *
 *   1. No admin session        → UnauthorizedAdminAccessException (403)
 *   2. Unknown caller record   → UnauthorizedAdminAccessException (403)
 *      (session adminId resolved to no admin — stale session)
 *   3. requested authority
 *      outranks the caller     → InsufficientAuthorityException   (403)
 *   4. Duplicate loginId       → LoginIdAlreadyTakenException     (409)
 *
 * Privilege-escalation guard: an admin MUST NOT create an account that
 * outranks them. Lower numeric authority = higher privilege, so the
 * refused case is `authority < caller.authority`. A peer-level account
 * is allowed — a system admin appointing another system admin is
 * ordinary shop staffing, and it hands out no privilege the caller does
 * not already hold. Raising an EXISTING admin's authority is a
 * different transition with a stricter rule
 * ({@see \MyVendor\BeMart\Be\Final\AuthorityRoleUpdated}).
 */
#[Be(MemberCreated::class)]
final readonly class MemberCreating
{
    public string $adminId;
    public string $passwordHash;
    public int $work;

    public function __construct(
        #[Input] public string $loginId,
        #[Input] #[SensitiveParameter] string $password,
        #[Input] public string $name,
        #[Input] public int $authority,
        #[Inject] AdminSession $adminSession,
        #[Inject] AdminQueryInterface $adminQuery,
        #[Inject] AdminIdProvider $ids,
        #[Inject] PasswordHasherInterface $passwordHasher,
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

        if ($authority < $caller->authority) {
            throw new InsufficientAuthorityException();
        }

        if ($adminQuery->byLogin($loginId) !== null) {
            throw new LoginIdAlreadyTakenException();
        }

        $this->adminId = $ids->get();
        $this->passwordHash = $passwordHasher->hash($password);
        $this->work = 1;
    }
}
