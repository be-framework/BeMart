<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\UsePointFormatException;

/**
 * Points spent (利用ポイント) — loyalty points the customer
 * applies to the order as a discount.
 *
 * Non-negative integer. Zero allowed when no points are used.
 *
 * Wave 7 extension: accepts null so partial-update flows (admin
 * doUpdateOrder) can pass `usePoint=null` for "do not change this field".
 */
final class UsePoint
{
    #[Validate]
    public function validate(int|null $usePoint): void
    {
        if ($usePoint === null) {
            return;
        }

        if ($usePoint < 0) {
            throw new UsePointFormatException();
        }
    }
}
