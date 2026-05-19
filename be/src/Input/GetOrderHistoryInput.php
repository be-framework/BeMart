<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\OrderHistoryFetched;

/**
 * Input for goOrderHistory — render the logged-in customer's full order
 * history (the "all orders" view, distinct from the dashboard summary).
 *
 *   GetOrderHistoryInput → OrderHistoryFetched (Direct, safe read)
 *
 * AUTHZ design — mass-assignment safety (Pilot 5 F-2 lesson, mirrored by
 * Pilot 8 / 12): the customerId INTENTIONALLY does not appear here. It
 * comes from the session exclusively, so a malicious client cannot view
 * another customer's history by tampering with request parameters.
 *
 * Pagination via `historyLimit` + `offset`:
 *   - `historyLimit` is bounded by the {@see HistoryLimit} Semantic
 *     (1—200). Distinct from {@see OrderLimit} (the goMypage dashboard
 *     cap, 1—50) because the dashboard panel must stay shallow while
 *     "full history" needs room. The default of 50 mirrors a reasonable
 *     first-page rendering.
 *   - `offset` is bounded by the {@see Offset} Semantic (0—10000). The
 *     upper bound is a safety rail — a tampered `offset=PHP_INT_MAX`
 *     does not propagate to storage.
 *
 * @link https://schema.org/ViewAction
 */
#[Be(OrderHistoryFetched::class)]
final readonly class GetOrderHistoryInput
{
    /**
     * @psalm-taint-source input $historyLimit
     * @psalm-taint-source input $offset
     */
    public function __construct(
        public int $historyLimit = 50,
        public int $offset = 0,
    ) {
    }
}
