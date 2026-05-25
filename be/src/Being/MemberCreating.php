<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\LoginIdAlreadyTakenException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberCreated;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;
use SensitiveParameter;

/**
 * The admin-member-being-created moment — Wave 8 (doCreateMember).
 *
 * Multi-Reason Being mirroring Wave 5O {@see AdminCustomerCreating},
 * with the admin AUTHZ guard as the first check:
 *
 *   0. AdminSessionInterface         — fail-fast if no admin session
 *   1. AdminQueryInterface           — fail-fast on duplicate loginId
 *   2. AdminIdGeneratorInterface     — opaque 32-char hex id
 *   3. PasswordHasherInterface       — bcrypt hash of plaintext password
 *
 * Existence of this object proves all four succeeded. The downstream
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
 * path. The check is at Being-time so the resource layer can map the
 * resulting exception to HTTP 403.
 *
 * Privilege-escalation NOTE: Wave 8 ships a deliberately lenient
 * create-policy — ANY logged-in admin may create another admin of ANY
 * authority. Tightening (e.g. "only system admin may create another
 * system admin") is a Phase 2 sweep; the role-flip transition
 * (doUpdateAuthorityRole) already enforces the stricter
 * caller.authority < target.authority rule, which limits the actual
 * damage from a permissive create.
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
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] AdminQueryInterface $adminQuery,
        #[Inject] AdminIdGeneratorInterface $idGenerator,
        #[Inject] PasswordHasherInterface $passwordHasher,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($adminQuery->findByLoginId($loginId) !== null) {
            throw new LoginIdAlreadyTakenException();
        }

        $this->adminId = $idGenerator->generate()->value;
        $this->passwordHash = $passwordHasher->hash($password);
        $this->work = 1;
    }
}
