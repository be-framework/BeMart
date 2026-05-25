<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Delivery method display name (Wave 9θ). Free-form, presentation-only.
 */
final class DeliveryName
{
    #[Validate]
    public function validate(string|null $deliveryName): void
    {
        // Type assertion only.
    }
}
