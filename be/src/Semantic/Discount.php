<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\DiscountFormatException;

/**
 * Order discount (値引き) — total discount applied to the order,
 * typically driven by redeemed customer points.
 *
 * Non-negative integer (yen). Zero allowed when no discount applies.
 */
final class Discount
{
    #[Validate]
    public function validate(int $discount): void
    {
        if ($discount < 0) {
            throw new DiscountFormatException();
        }
    }
}
