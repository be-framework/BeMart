<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\TotalPriceFormatException;

/**
 * Cart total price (税抜) — sum of (unitPrice * quantity) across cart items.
 *
 * 0 — 9,999,999,999. Zero allowed (empty cart or all-free items).
 */
final class TotalPrice
{
    #[Validate]
    public function validate(int $totalPrice): void
    {
        if ($totalPrice < 0 || $totalPrice > 9_999_999_999) {
            throw new TotalPriceFormatException();
        }
    }
}
