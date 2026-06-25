<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Requested お届け時間 — the delivery time-slot chosen on /shopping
 * (a deliveryOptions time value, or '' for 指定なし). Presentation/transport
 * value; type assertion only.
 */
final class DeliveryTime
{
    #[Validate]
    public function validate(string|null $deliveryTime): void
    {
        // Type assertion only — the slot set is offered by deliveryOptions.
    }
}
