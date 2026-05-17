<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SubtotalFormatException;

/**
 * Order subtotal (税抜・配送料前) — sum of line totals before tax,
 * delivery fee, payment charge and point discount.
 *
 * Non-negative integer (yen). Zero allowed for all-free orders.
 */
final class Subtotal
{
    #[Validate]
    public function validate(int $subtotal): void
    {
        if ($subtotal < 0) {
            throw new SubtotalFormatException();
        }
    }
}
