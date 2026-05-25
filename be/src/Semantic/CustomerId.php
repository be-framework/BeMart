<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Customer id — server-derived. Provided by
 * CustomerIdProvider. Type assertion only — the provider
 * itself is the contract (UUID-like opaque string).
 */
final class CustomerId
{
    #[Validate]
    public function validate(string $customerId): void
    {
        // Type assertion only — provider is the contract.
    }
}
