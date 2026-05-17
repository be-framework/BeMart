<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Being\QuantityAdjusted;

/**
 * Input for doAddCartItem — add a product to the active cart.
 *
 * Both fields are validated by Semantic at Becoming time
 * (productCode → Semantic\ProductCode, quantity → Semantic\Quantity).
 *
 * The cascade is:
 *   AddCartItemInput → QuantityAdjusted (Stage 1 Being) → CartItemAdded (Final).
 *
 * @link https://schema.org/AddAction
 */
#[Be([QuantityAdjusted::class])]
final readonly class AddCartItemInput
{
    public function __construct(
        public string $productCode,
        public int $quantity,
        public string $sessionPrefix = 'session-prefix-1',
    ) {
    }
}
