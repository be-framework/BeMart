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
     * `orderNo` originates from the HTTP request body. `sessionPrefix`
     * is resolved by the Resource from CartSessionPrefixInterface — it
     * has no default, because an omitted prefix would silently merge
     * every customer's reorder into one shared cart partition.
     *
     * @psalm-taint-source input $orderNo
     */
    public function __construct(
        public string $orderNo,
        public string $sessionPrefix,
    ) {
    }
}
