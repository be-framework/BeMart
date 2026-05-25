<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminOrderCreated;

/**
 * Input for doCreateOrder — admin creates a finalized order from
 * scratch (Wave 9η, **Phase 2 simplification**).
 *
 *   AdminCreateOrderInput → AdminOrderCreated  (Direct, unsafe)
 *
 * ALPS doc verbatim: "管理画面から手動で受注を新規作成する。
 * PurchaseFlow(orderフロー)で税・送料・在庫を計算。" Admin-created
 * orders are an exotic EC-CUBE feature (back-office data-entry for
 * phone / FAX orders) — they bypass Cart, PaymentMethod::verify(), and
 * the customer-side checkout entirely. The Wave 9η iteration covers
 * the AUTHZ + URL surface only; the PurchaseFlow recompute (tax /
 * delivery / stock) is Phase 2 scope.
 *
 * Editable fields kept narrow: customerId + a handful of money
 * columns. Every other dtb_order column is server-derived (orderNo
 * from {@see \MyVendor\BeMart\Be\Reason\Provider\OrderNoProvider},
 * orderStatus=NEW(1), orderDate=now, addPoint=0, derived totals from
 * subtotal+tax+deliveryFeeTotal+charge-discount) — same mass-
 * assignment discipline as {@see AdminUpdateOrderInput} (Pilot 5 F-2
 * lesson).
 *
 * @link https://schema.org/CreateAction
 */
#[Be(AdminOrderCreated::class)]
final readonly class AdminCreateOrderInput
{
    /**
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $paymentMethodId
     * @psalm-taint-source input $subtotal
     * @psalm-taint-source input $deliveryFeeTotal
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $discount
     * @psalm-taint-source input $tax
     */
    public function __construct(
        public string $customerId,
        public int $paymentMethodId,
        public int $subtotal,
        public int $deliveryFeeTotal = 0,
        public int $charge = 0,
        public int $discount = 0,
        public int $tax = 0,
    ) {
    }
}
