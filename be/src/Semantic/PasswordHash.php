<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Password hash — server-derived. Output of PasswordHasherInterface
 * (e.g. password_hash with PASSWORD_DEFAULT). Type assertion only;
 * the hasher is the contract.
 */
final class PasswordHash
{
    #[Validate]
    public function validate(string $passwordHash): void
    {
        // Type assertion only — hasher is the contract.
    }
}
