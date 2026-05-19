<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Being\CartItemQuantityReplacing;

/**
 * Input for doUpdateCartItemQuantity — Pilot 10.
 *
 * Linear pattern (contact-form demo):
 *   Input → CartItemQuantityReplacing (Being) → CartItemQuantityUpdated (Final)
 *
 * Replaces the quantity of an EXISTING item in the cart (not addition
 * — that's Pilot 2 doAddCartItem). The new quantity is re-capped
 * against current stock and saleLimit; if EC-CUBE's PurchaseFlow
 * later adjusts further, that's transparent.
 *
 * Idempotent: re-sending the same quantity twice produces the same
 * cart state. Quantity 0 is rejected by Semantic\Quantity — use
 * doRemoveCartItem (Pilot 11) for removal.
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(CartItemQuantityReplacing::class)]
final readonly class UpdateCartItemQuantityInput
{
    /**
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $quantity
     * @psalm-taint-source input $sessionPrefix
     */
    public function __construct(
        public string $productCode,
        public int $quantity,
        public string $sessionPrefix = 'session-prefix-1',
    ) {
    }
}
