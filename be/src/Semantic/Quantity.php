<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\QuantityFormatException;

/**
 * Cart item quantity — EC-CUBE 4.3 dtb_cart_item.quantity.
 *
 * Positive integer, 1 — 999. Observed Phase 2 range was 1 — 99;
 * upper bound widened to 999 per EC-CUBE admin-screen convention.
 */
final class Quantity
{
    #[Validate]
    public function validate(int $quantity): void
    {
        if ($quantity < 1 || $quantity > 999) {
            throw new QuantityFormatException();
        }
    }
}
