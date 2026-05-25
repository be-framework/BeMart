<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\InvalidPriceException;

/**
 * Selling price (税抜) — EC-CUBE 4.3 dtb_product_class.price02.
 *
 * 0 — 9,999,999,999 (10 digits). Zero allowed for free items.
 *
 * Wave 8 extension: accepts null so partial-update flows
 * (doUpdateProduct) can pass `price02=null` to mean "do not change
 * this field".
 */
final class Price02
{
    #[Validate]
    public function validate(int|null $price02): void
    {
        if ($price02 === null) {
            return;
        }

        if ($price02 < 0 || $price02 > 9_999_999_999) {
            throw new InvalidPriceException();
        }
    }
}
