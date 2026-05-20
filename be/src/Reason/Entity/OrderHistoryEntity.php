<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Enriched order-history projection — the screen aggregate behind
 * goMypageHistory (EC-CUBE `Mypage/history.twig`).
 *
 * Phase 3 enrichment. The earlier {@see MypageHistoryFetched} projection
 * was a thin header (totals + a flat `items` list); EC-CUBE's history
 * screen renders far more: per-shipping address blocks, the payment
 * method, the customer's order message and the mail-delivery history.
 * This entity composes all of it so the History resource body — and the
 * ported Twig template — can render the screen at full fidelity.
 *
 * It is a deliberately separate read projection from {@see FinalizedOrderEntity}:
 * the header entity is consumed by ~20 Order/admin flows and intentionally
 * keeps a narrow column set. Enriching the history view via a dedicated
 * projection avoids rippling a constructor change across all of them.
 *
 * Mapping (read side — {@see \MyVendor\BeMart\Be\Reason\Query\SqlOrderQuery}):
 *   - header / totals / points / status → `dtb_order`
 *   - `message`                         → `dtb_order.message`
 *   - `paymentMethod`                   → `dtb_payment.payment_method`
 *     (JOIN on `dtb_order.payment_id`; empty string when unset)
 *   - `shippings[]`                     → `dtb_shipping` rows for the
 *     order, each carrying its `dtb_order_item` rows grouped by
 *     `shipping_id`
 *   - `mailHistories[]`                 → `dtb_mail_history` rows for
 *     the order, oldest send first
 *
 * `customerId` is carried so the Final can run the order-ownership AUTHZ
 * check directly off this projection (no second `byOrderNo` round-trip).
 */
final readonly class OrderHistoryEntity
{
    /**
     * @param list<OrderHistoryShippingEntity> $shippings
     * @param list<OrderHistoryMailEntity>     $mailHistories
     */
    public function __construct(
        public string $orderNo,
        public string $customerId,
        public string $message,
        public string $paymentMethod,
        public int $subtotal,
        public int $deliveryFeeTotal,
        public int $charge,
        public int $discount,
        public int $tax,
        public int $total,
        public int $paymentTotal,
        public int $addPoint,
        public int $usePoint,
        public int $orderStatus,
        public string $orderDate,
        public string $paymentDate,
        public array $shippings,
        public array $mailHistories,
    ) {
    }
}
