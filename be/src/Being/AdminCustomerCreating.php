<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerCreated;
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessCheckerInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerInitialPointInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;
use SensitiveParameter;

/**
 * The customer-being-created (admin-side) moment.
 *
 * Multi-Reason Being (blog-publishing demo) mirroring Pilot 4's
 * {@see CustomerRegistering}, with the admin AUTHZ guard added as the
 * very first check:
 *
 *   0. AdminSessionInterface          — fail-fast if no admin session
 *   1. EmailUniquenessCheckerInterface — fail-fast on duplicate email
 *   2. CustomerIdGeneratorInterface    — opaque 32-char hex id
 *   3. PasswordHasherInterface         — bcrypt hash of plaintext password
 *   4. CustomerInitialPointInterface   — registration bonus points
 *
 * Existence of this object proves all five succeeded. The downstream
 * Final ({@see AdminCustomerCreated}) only has to persist a
 * CustomerEntity built from this public surface.
 *
 * `customerStatus` is fixed to 2 (Active) — ALPS doc for
 * doCreateCustomer: "仮会員フラグなしで即時本会員として登録". No
 * provisional state, no email-verification round-trip.
 *
 * AUTHZ rationale: admin and customer are parallel firewalls (Wave 4
 * decision). A logged-in customer is NOT logged-in-as-admin and must
 * not reach this code path. The check is at Being-time so the
 * resource layer can map the resulting exception to HTTP 403.
 *
 * Password handling: the plaintext `$password` is consumed inside the
 * constructor and intentionally NOT promoted to a public property —
 * the downstream Final receives `$passwordHash` only, the same
 * discipline as {@see CustomerRegistering}.
 */
#[Be(AdminCustomerCreated::class)]
final readonly class AdminCustomerCreating
{
    public string $customerId;
    public string $passwordHash;
    public int $initialPoint;
    public int $customerStatus;

    public function __construct(
        #[Input] public string $email,
        #[Input] #[SensitiveParameter] string $password,
        #[Input] public string $name01,
        #[Input] public string $name02,
        #[Input] public string|null $kana01,
        #[Input] public string|null $kana02,
        #[Input] public string|null $companyName,
        #[Input] public string|null $phoneNumber,
        #[Input] public string|null $postalCode,
        #[Input] public int|null $pref,
        #[Input] public string|null $addr01,
        #[Input] public string|null $addr02,
        #[Input] public string|null $birth,
        #[Input] public int|null $sex,
        #[Input] public int|null $job,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] EmailUniquenessCheckerInterface $uniquenessChecker,
        #[Inject] CustomerIdGeneratorInterface $idGenerator,
        #[Inject] PasswordHasherInterface $passwordHasher,
        #[Inject] CustomerInitialPointInterface $initialPointService,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $uniqueness = $uniquenessChecker->check($email);
        /** @psalm-suppress InvalidDocblock Psalm treats assert* methods as assertion helpers. */
        $uniqueness->assertUnique();

        $this->customerId = $idGenerator->generate();
        $this->passwordHash = $passwordHasher->hash($password);
        $this->initialPoint = $initialPointService->initial();
        $this->customerStatus = 2;
    }
}
