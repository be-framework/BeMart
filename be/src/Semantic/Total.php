<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\TotalFormatException;

/**
 * Order total (合計) — subtotal + tax + delivery fee + payment charge
 * - discount. The final amount the customer owes.
 *
 * Non-negative integer (yen). Zero allowed when discounts fully offset
 * the order.
 */
final class Total
{
    #[Validate]
    public function validate(int $total): void
    {
        if ($total < 0) {
            throw new TotalFormatException();
        }
    }
}
