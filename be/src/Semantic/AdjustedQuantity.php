<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\AdjustedQuantityFormatException;

/**
 * Adjusted cart item quantity — quantity after stock cap and saleLimit
 * cap have been applied.
 *
 * Positive integer, 1 — 999. Always less than or equal to the
 * corresponding requestedQuantity.
 */
final class AdjustedQuantity
{
    #[Validate]
    public function validate(int $adjustedQuantity): void
    {
        if ($adjustedQuantity < 1 || $adjustedQuantity > 999) {
            throw new AdjustedQuantityFormatException();
        }
    }
}
