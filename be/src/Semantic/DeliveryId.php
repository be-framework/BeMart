<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Delivery master id — server-derived. Provided by
 * DeliveryIdProvider (Wave 9θ). Type assertion only — the
 * provider is the contract.
 */
final class DeliveryId
{
    #[Validate]
    public function validate(string|null $deliveryId): void
    {
        // Type assertion only — provider is the contract.
    }
}
