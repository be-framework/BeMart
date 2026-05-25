<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\DeliveryFeeFormatException;

/**
 * Per-item delivery fee — EC-CUBE 4.3 dtb_product_class.delivery_fee.
 *
 * 0 — 9,999,999,999. Zero allowed for shipping-free items.
 */
final class DeliveryFee
{
    #[Validate]
    public function validate(int $deliveryFee): void
    {
        if ($deliveryFee < 0 || $deliveryFee > 9_999_999_999) {
            throw new DeliveryFeeFormatException();
        }
    }
}
