<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Persisted Order aggregate (dtb_order, orderStatus=NEW(1)).
 *
 * EC-CUBE's PurchaseFlow promotes the pre-order (orderStatus=PROCESSING(8))
 * through PENDING(7) before landing at NEW(1) once checkout() succeeds. The
 * Pilot 5 fake collapses the intermediate transitions and only persists the
 * terminal NEW state, since the intermediate values are an EC-CUBE
 * implementation detail (state machine for crash recovery) rather than a
 * product-visible state.
 *
 * Only the fields the Final exposes to ShoppingComplete are modeled;
 * shipping rows, order-item snapshots and tax-line breakdowns are deferred
 * to the production migration where they land in dedicated tables.
 */
final readonly class FinalizedOrderEntity
{
    public const STATUS_NEW = 1;

    public function __construct(
        public string $orderNo,
        public string $preOrderId,
        public string $customerId,
        public int $paymentMethodId,
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
    ) {
    }
}
