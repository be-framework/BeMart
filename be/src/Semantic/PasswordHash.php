<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Password hash — server-derived. Output of PasswordHasherInterface
 * (e.g. password_hash with PASSWORD_DEFAULT). Type assertion only;
 * the hasher is the contract.
 *
 * Nullable: the admin customer-edit Final (AdminCustomerUpdated)
 * receives `null` to mean "no new password was supplied — keep the
 * persisted hash". Flows that always re-hash pass a non-nullable
 * `string`, so the type assertion is only meaningful when a value is
 * actually present.
 */
final class PasswordHash
{
    #[Validate]
    public function validate(string|null $passwordHash): void
    {
        // Type assertion only — hasher is the contract.
    }
}
