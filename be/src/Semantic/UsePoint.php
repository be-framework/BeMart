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
 */
final class UsePoint
{
    #[Validate]
    public function validate(int $usePoint): void
    {
        if ($usePoint < 0) {
            throw new UsePointFormatException();
        }
    }
}
