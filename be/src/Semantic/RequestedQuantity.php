<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\RequestedQuantityFormatException;

/**
 * Requested cart item quantity — the quantity the customer asked for,
 * before stock/saleLimit adjustment.
 *
 * Positive integer, 1 — 999 (mirrors Semantic\Quantity range).
 */
final class RequestedQuantity
{
    #[Validate]
    public function validate(int $requestedQuantity): void
    {
        if ($requestedQuantity < 1 || $requestedQuantity > 999) {
            throw new RequestedQuantityFormatException();
        }
    }
}
