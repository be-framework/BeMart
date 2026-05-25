<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Being\ReorderResolving;

/**
 * Input for doReorder — repopulate the customer's cart from a past order.
 *
 * The cascade is:
 *   ReorderInput → ReorderResolving (Stage 1 Being: AUTHZ + past-items
 *   load + per-item current ProductClass resolution + cap application)
 *   → Reordered (Final: per-saleType cart merge + persist).
 *
 * Out-of-stock or discontinued products are skipped (not raised),
 * matching the ALPS contract: 在庫切れ商品はスキップ、現在価格を適用.
 *
 * @link https://schema.org/OrderAction
 */
#[Be(ReorderResolving::class)]
final readonly class ReorderInput
{
    /**
     * Phase B Slice 9: `orderNo` originates from the HTTP request body.
     * `sessionPrefix` has a default but can be overridden by a future
     * Resource call — also treat as input.
     *
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $sessionPrefix
     */
    public function __construct(
        public string $orderNo,
        public string $sessionPrefix = 'session-prefix-1',
    ) {
    }
}
