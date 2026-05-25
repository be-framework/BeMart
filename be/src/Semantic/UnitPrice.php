<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\UnitPriceFormatException;

/**
 * Unit selling price (税抜) — per-item price snapshot at cart time.
 *
 * 0 — 9,999,999,999. Zero allowed for free items. Mirrors the
 * Price02 range since this is sourced from dtb_product_class.price02.
 */
final class UnitPrice
{
    #[Validate]
    public function validate(int $unitPrice): void
    {
        if ($unitPrice < 0 || $unitPrice > 9_999_999_999) {
            throw new UnitPriceFormatException();
        }
    }
}
