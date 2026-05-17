<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CartItemAdded;

/**
 * Input for doAddCartItem — add a product to the active cart.
 *
 * Both fields are validated by Semantic at Becoming time
 * (productCode → Semantic\ProductCode, quantity → Semantic\Quantity).
 *
 * @link https://schema.org/AddAction
 */
#[Be([CartItemAdded::class])]
final readonly class AddCartItemInput
{
    public function __construct(
        public string $productCode,
        public int $quantity,
        public string $sessionPrefix = 'session-prefix-1',
    ) {
    }
}
