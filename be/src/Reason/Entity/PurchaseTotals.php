<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Aggregated totals produced by PurchaseFlow (shopping flow).
 *
 * The ShoppingConfirm state in ALPS exposes exactly these scalars; the
 * PurchaseFlowApplied Being holds an instance of this class as its
 * convergence output.
 */
final readonly class PurchaseTotals
{
    public function __construct(
        public int $subtotal,
        public int $deliveryFeeTotal,
        public int $charge,
        public int $discount,
        public int $tax,
        public int $total,
        public int $paymentTotal,
        public int $addPoint,
        public int $usePoint,
    ) {
    }
}
