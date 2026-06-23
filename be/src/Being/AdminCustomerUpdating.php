<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerUpdated;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\EmailUniquenessQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\PasswordHasherInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;
use SensitiveParameter;

/**
 * The customer-being-updated (admin-side) moment.
 *
 * Linear Being (contact-form demo) mirroring {@see AdminCustomerCreating},
 * with the admin AUTHZ guard as the very FIRST statement and the edit-
 * specific guards layered after it:
 *
 *   0. AdminSession                  — fail-fast if no admin session
 *   1. CustomerQueryInterface::item  — fail-fast if the target id is unknown
 *   2. EmailUniquenessQueryInterface — fail-fast on duplicate email, but
 *      ONLY when the email actually changed (same guard as CustomerUpdated)
 *   3. PasswordHasherInterface       — re-hash, but ONLY when a new
 *      password was supplied (null = keep the current hash)
 *
 * AUTHZ rationale: the admin-supplied `customerId` is a foreign id; it
 * must NEVER be used to read or rewrite a customer record unless the
 * admin firewall has granted access. The check is the first statement
 * (before the load) so an anonymous caller can never probe existence
 * or mutate PII. The resource maps the resulting exception to HTTP 403.
 *
 * Password handling: the plaintext `$password` is consumed inside the
 * constructor and intentionally NOT promoted to a public property — the
 * downstream Final receives `$passwordHash` only (null sentinel = leave
 * the persisted hash untouched), the same discipline as
 * {@see AdminCustomerCreating}.
 *
 * Out of slice (residual, mirroring the template's omitted status
 * select): customerStatus / point editing and the withdrawal branch.
 */
#[Be(AdminCustomerUpdated::class)]
final readonly class AdminCustomerUpdating
{
    public string|null $passwordHash;

    public function __construct(
        #[Input] public string $customerId,
        #[Input] public string $email,
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
        #[Input] #[SensitiveParameter] string|null $password,
        #[Inject] AdminSession $adminSession,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] EmailUniquenessQueryInterface $uniquenessChecker,
        #[Inject] PasswordHasherInterface $passwordHasher,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $customerQuery->item($customerId);
        if ($current === null) {
            throw new CustomerNotFoundException();
        }

        if ($email !== $current->email) {
            $uniqueness = $uniquenessChecker->item($email);
            /** @psalm-suppress InvalidDocblock Psalm treats assert* methods as assertion helpers. */
            $uniqueness->assertUnique();
        }

        $this->passwordHash = $password === null ? null : $passwordHasher->hash($password);
    }
}
