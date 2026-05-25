<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\DeliveryFeeTotalFormatException;

/**
 * Cart-wide delivery fee total — sum of (deliveryFee * quantity)
 * across all cart items.
 *
 * 0 — 9,999,999,999. Zero allowed when every item is shipping-free.
 */
final class DeliveryFeeTotal
{
    #[Validate]
    public function validate(int $deliveryFeeTotal): void
    {
        if ($deliveryFeeTotal < 0 || $deliveryFeeTotal > 9_999_999_999) {
            throw new DeliveryFeeTotalFormatException();
        }
    }
}
