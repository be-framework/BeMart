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
 *
 * Wave 7 extension: accepts null so partial-update flows (admin
 * doUpdateOrder) can pass `discount=null` for "do not change this field".
 */
final class Discount
{
    #[Validate]
    public function validate(int|null $discount): void
    {
        if ($discount === null) {
            return;
        }

        if ($discount < 0) {
            throw new DiscountFormatException();
        }
    }
}
