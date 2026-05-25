<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CartItemRemoved;

/**
 * Input for doRemoveCartItem — Pilot 11.
 *
 * Direct pattern: Input → Final. The Final scans the session's carts
 * for the productCode and removes it.
 *
 *   RemoveCartItemInput → CartItemRemoved (Final)
 *
 * Idempotent: re-removing an already-removed item raises
 * CartItemNotInCartException so the BEAR layer returns 404. Some
 * codebases prefer to swallow this for true idempotence, but
 * surfacing it lets the UI distinguish "you clicked twice" from
 * "the cart was actually empty".
 *
 * @link https://schema.org/DeleteAction
 */
#[Be(CartItemRemoved::class)]
final readonly class RemoveCartItemInput
{
    /**
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $sessionPrefix
     */
    public function __construct(
        public string $productCode,
        public string $sessionPrefix = 'session-prefix-1',
    ) {
    }
}
