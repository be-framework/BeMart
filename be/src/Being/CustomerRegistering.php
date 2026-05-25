<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CustomerRegistered;
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessCheckerInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerInitialPointInterface;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;
use SensitiveParameter;

/**
 * The customer-being-registered moment.
 *
 * Multi-Reason Being (blog-publishing demo): four independent Reasons
 * cooperate to derive server-owned scalars from the validated input,
 * without any of them forming a Diamond.
 *
 *   1. EmailUniquenessCheckerInterface — fail-fast on duplicate email
 *   2. CustomerIdGeneratorInterface    — opaque 32-char hex id
 *   3. PasswordHasherInterface         — bcrypt hash of plaintext password
 *   4. CustomerInitialPointInterface   — registration bonus points
 *
 * Existence of this object proves all four succeeded. The next stage
 * (`CustomerRegistered`) only has to persist a CustomerEntity built
 * from this public surface, so persistence is isolated from the
 * "policy" decisions taken here.
 *
 * `customerStatus` is fixed to 2 (Active) — see RegisterCustomerInput
 * docblock for the Pilot 4 scope decision.
 *
 * Password handling: the plaintext `$password` is consumed inside the
 * constructor and is intentionally NOT promoted to a public property.
 * The downstream Final receives `$passwordHash` only, so the plaintext
 * never travels further than this constructor scope. The
 * `#[SensitiveParameter]` attribute also redacts the value from PHP
 * stack traces (an unhandled exception inside the Reasons would
 * otherwise expose it).
 */
#[Be(CustomerRegistered::class)]
final readonly class CustomerRegistering
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
        #[Inject] EmailUniquenessCheckerInterface $uniquenessChecker,
        #[Inject] CustomerIdGeneratorInterface $idGenerator,
        #[Inject] PasswordHasherInterface $passwordHasher,
        #[Inject] CustomerInitialPointInterface $initialPointService,
    ) {
        $uniquenessChecker->ensureUnique($email);

        $this->customerId = $idGenerator->generate();
        $this->passwordHash = $passwordHasher->hash($password);
        $this->initialPoint = $initialPointService->initial();
        $this->customerStatus = 2;
    }
}
