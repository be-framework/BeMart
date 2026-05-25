<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\TaxFormatException;

/**
 * Order consumption tax (消費税) — total tax amount computed
 * across all order lines.
 *
 * Non-negative integer (yen). Zero allowed when no taxable items.
 */
final class Tax
{
    #[Validate]
    public function validate(int $tax): void
    {
        if ($tax < 0) {
            throw new TaxFormatException();
        }
    }
}
