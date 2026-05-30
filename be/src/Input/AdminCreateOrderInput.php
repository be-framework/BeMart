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
 * the customer-side checkout entirely.
 *
 * The admin posts the purchased line items (`orderItems`) plus the
 * delivery / charge / discount money columns. `subtotal`, `tax`,
 * `total`, `paymentTotal` and `addPoint` are NOT trusted from the
 * client — {@see AdminOrderCreated} recomputes them from the line items
 * through the shared {@see \MyVendor\BeMart\Be\Reason\Service\PurchaseFlowInterface}
 * (the same recompute the storefront checkout runs), then persists the
 * dtb_order_item snapshot. `orderNo` is server-allocated, orderStatus
 * fixed to NEW(1) — same mass-assignment discipline as
 * {@see AdminUpdateOrderInput} (Pilot 5 F-2 lesson).
 *
 * Each `orderItems` entry is a 4-tuple of {productCode, productName,
 * unitPrice, quantity} — validated by the {@see \MyVendor\BeMart\Be\Semantic\OrderItems}
 * semantic.
 *
 * @link https://schema.org/CreateAction
 */
#[Be(AdminOrderCreated::class)]
final readonly class AdminCreateOrderInput
{
    /**
     * @param list<array{productCode: string, productName: string, unitPrice: int, quantity: int}> $orderItems
     *
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $paymentMethodId
     * @psalm-taint-source input $orderItems
     * @psalm-taint-source input $deliveryFeeTotal
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $discount
     */
    public function __construct(
        public string $customerId,
        public int $paymentMethodId,
        public array $orderItems,
        public int $deliveryFeeTotal = 0,
        public int $charge = 0,
        public int $discount = 0,
    ) {
    }
}
