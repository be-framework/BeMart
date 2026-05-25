<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CartsFetched;

/**
 * Input for goCart — read the current shopping session's carts.
 *
 * EC-CUBE partitions a shopping session into N carts (one per
 * saleType), keyed by `{sessionPrefix}_{saleTypeId}`. This Input
 * carries the sessionPrefix; the Final scans for all carts under it.
 *
 *   GetCartsInput → CartsFetched (Final — Direct)
 *
 * Safe read: no side effects, no AUTHZ (the cart belongs to whoever
 * has the sessionPrefix cookie; ownership is implicit). The
 * sessionPrefix default mirrors AddCartItemInput's so Pilot 2's
 * fixtures are visible.
 */
#[Be(CartsFetched::class)]
final readonly class GetCartsInput
{
    /**
     * @psalm-taint-source input $sessionPrefix
     */
    public function __construct(
        public string $sessionPrefix = 'session-prefix-1',
    ) {
    }
}
