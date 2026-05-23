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
final readonly class FinalizedOrderEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    /**
     * EC-CUBE dtb_order.order_status_id values, mirrored verbatim from the
     * ALPS `orderStatus` descriptor:
     *   1=新規受付 / 3=注文取消 / 4=対応中 / 5=発送済み / 6=入金済み /
     *   7=決済処理中 / 8=購入処理中 / 9=返品
     *
     * Pilot 5 (CheckoutCompleted) introduced STATUS_NEW. Wave 7 (admin
     * order management) adds the rest — Symfony Workflow's transition
     * map (pay 1→6, packing 1,6→4, cancel 1,4,6→3, ship 1,6,4→5,
     * return 5→9, cancel_return 9→5, back_to_in_progress 3→4) is
     * enforced by {@see OrderStatus} Semantic + admin flows that consult
     * the current row.
     *
     * 7 / 8 are PurchaseFlow-internal (not reachable via the admin
     * status-flip flow); they are present here so a status read from
     * storage round-trips losslessly.
     */
    public const STATUS_NEW           = 1;
    public const STATUS_CANCEL        = 3;
    public const STATUS_IN_PROGRESS   = 4;
    public const STATUS_DELIVERED     = 5;
    public const STATUS_PAID          = 6;
    public const STATUS_PENDING       = 7;
    public const STATUS_PROCESSING    = 8;
    public const STATUS_RETURNED      = 9;

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
