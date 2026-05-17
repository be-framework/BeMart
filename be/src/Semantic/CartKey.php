<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\CartKeyFormatException;

use function preg_match;

/**
 * Cart partition key — {sessionPrefix}_{saleTypeId}.
 *
 * Composite identifier that isolates carts by sale type (normal vs.
 * pre-order). Format: non-empty prefix, underscore, positive integer.
 */
final class CartKey
{
    #[Validate]
    public function validate(string $cartKey): void
    {
        if (! preg_match('/^.+_[1-9][0-9]*$/', $cartKey)) {
            throw new CartKeyFormatException();
        }
    }
}
