<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\AddPointFormatException;

/**
 * Points awarded (獲得ポイント) — loyalty points granted to the
 * customer upon order confirmation.
 *
 * Non-negative integer. Zero allowed when no points are awarded.
 */
final class AddPoint
{
    #[Validate]
    public function validate(int $addPoint): void
    {
        if ($addPoint < 0) {
            throw new AddPointFormatException();
        }
    }
}
