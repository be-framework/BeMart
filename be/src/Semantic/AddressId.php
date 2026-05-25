<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Address id — server-derived. Provided by
 * AddressIdProvider (Pilot 16). Type assertion only —
 * the provider itself is the contract (opaque hex string).
 */
final class AddressId
{
    #[Validate]
    public function validate(string $addressId): void
    {
        // Type assertion only — provider is the contract.
    }
}
