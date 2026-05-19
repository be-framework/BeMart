<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminOrderStatusUpdated;

/**
 * Input for doUpdateOrderStatus — admin flips one finalized order's
 * status column.
 *
 *   AdminUpdateOrderStatusInput → AdminOrderStatusUpdated  (Direct,
 *                                                          idempotent)
 *
 * AUTHZ — cross-firewall (Wave 4 lesson): the Final pulls the adminId
 * from {@see \MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface}.
 * No admin session → 403. Unknown orderNo → 404.
 *
 * `orderStatus` is bounded by the {@see \MyVendor\BeMart\Be\Semantic\OrderStatus}
 * Semantic — only EC-CUBE's recognised dtb_order_status values (1, 3-9)
 * pass the input boundary. The Semantic enforces format only; the
 * Final does NOT enforce Symfony Workflow's transition map (Phase 2
 * concern — for now any allowed value flips through). Parameter name
 * `orderStatus` (rather than `newStatus`) matches the Semantic class
 * name verbatim per the Be Framework convention.
 *
 * Idempotency: when `orderStatus` equals the persisted `orderStatus`,
 * the Final short-circuits — same projection shape with a
 * `changed=false` flag, no write. Replay safety matches the
 * AdminCustomerDeleted `alreadyDeleted` convention (Wave 6).
 *
 * Mass-assignment safety: the adminId is NOT a constructor parameter —
 * it comes from the session. The only request-controlled inputs are
 * the target `orderNo` and the desired `orderStatus`.
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(AdminOrderStatusUpdated::class)]
final readonly class AdminUpdateOrderStatusInput
{
    /**
     * Wave 7: both fields are admin-form input (the orderNo is the
     * target picker, `newStatus` is the value the admin clicked).
     * Same taint discipline as the rest of Wave 7.
     *
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $orderStatus
     */
    public function __construct(
        public string $orderNo,
        public int $orderStatus,
    ) {
    }
}
