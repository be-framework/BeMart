<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminOrderListFetched;

/**
 * Input for goOrderList — admin lists all finalized orders.
 *
 *   GetAdminOrderListInput → AdminOrderListFetched  (Direct, safe read)
 *
 * Admin-only endpoint. AUTHZ lives in the Final via the Wave 4
 * {@see \MyVendor\BeMart\Be\Reason\Service\AdminSession} —
 * a null admin session raises
 * {@see \MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException},
 * which the BEAR layer maps to 403. Distinct from the customer-side
 * 401 (no AUTHN): admin and customer are parallel firewalls (Wave 4
 * lesson), and a logged-in customer is NOT logged-in-as-admin.
 *
 * Pagination via `limit` + `offset`, mirroring Wave 6R's GetOrderHistory
 * pattern:
 *   - `limit`  bounded by {@see \MyVendor\BeMart\Be\Semantic\Limit}     (1–50)
 *   - `offset` bounded by {@see \MyVendor\BeMart\Be\Semantic\Offset}    (0–10000)
 *
 * Filter scope (Wave 7 first iteration): defaults only — every finalized
 * order is in scope, paged by `limit` + `offset`. The original EC-CUBE
 * admin form additionally supports orderNo / customerName / dateRange /
 * orderStatus / paymentMethod / deliveryMethod filters; those are
 * Phase 2 scope (the highest-traffic admin tasks — drill-down by
 * orderNo and per-order status flip — are the focus here).
 *
 * @link https://schema.org/SearchAction
 */
#[Be(AdminOrderListFetched::class)]
final readonly class GetAdminOrderListInput
{
    /**
     * Wave 7: pagination knobs are admin-form input (query string in the
     * admin UI). Same taint discipline as the customer-side OrderHistory.
     *
     * @psalm-taint-source input $limit
     * @psalm-taint-source input $offset
     */
    public function __construct(
        public int $limit = 50,
        public int $offset = 0,
    ) {
    }
}
