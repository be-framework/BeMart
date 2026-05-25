<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminOrderFetched;

/**
 * Input for goOrder — admin views one order's detail page.
 *
 *   GetAdminOrderInput → AdminOrderFetched  (Direct, safe read)
 *
 * Admin-only endpoint. AUTHZ lives in the Final via
 * {@see \MyVendor\BeMart\Be\Reason\Service\AdminSession} — a
 * null admin session raises {@see \MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException}
 * (403). Unknown orderNo raises
 * {@see \MyVendor\BeMart\Be\Exception\OrderNotFoundException} (404).
 *
 * Mass-assignment safety: the adminId is read exclusively from the
 * AdminSession; it is NOT a constructor parameter. The only request-
 * controlled input is `$orderNo` (the target — which order the admin
 * is inspecting). Customer-side ownership AUTHZ is NOT applied here:
 * an admin who has crossed the admin firewall is permitted to view ANY
 * customer's order (that is the point of the back-office screen).
 *
 * @link https://schema.org/ViewAction
 */
#[Be(AdminOrderFetched::class)]
final readonly class GetAdminOrderInput
{
    /**
     * Wave 7: the orderNo comes from the admin UI (clicked from the
     * order-list row, or pasted into the URL). Same taint discipline as
     * the customer-side Reorder.
     *
     * @psalm-taint-source input $orderNo
     */
    public function __construct(
        public string $orderNo,
    ) {
    }
}
