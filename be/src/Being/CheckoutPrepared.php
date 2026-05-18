<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseFlowResult;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\PurchaseFlowInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Stage 1 Being — pre-order resolved and totals applied.
 *
 * Combines two Reasons in one Being (Pilot 5 Cascade Multi-Reason):
 *
 *   1. `OrderQueryInterface` — proves a pre-order with
 *      orderStatus=PROCESSING(8) exists for the given preOrderId. Throws
 *      `PreOrderNotFoundException` on miss.
 *   2. `PurchaseFlowInterface` — runs the EC-CUBE shopping flow against the
 *      pre-order to compute the totals (subtotal / tax / etc.). The output
 *      is the same shape as ShoppingConfirm and matches Pilot 3's
 *      `PurchaseFlowApplied`.
 *
 * Existence of this object guarantees: pre-order exists AND totals have
 * been computed. Downstream Beings can rely on both without re-validating.
 *
 * The merged-stage choice (vs. Pilot 3's separate `PreOrderResolved` +
 * `PurchaseFlowApplied`) is deliberate. Pilot 3 split them to let the
 * verify-failure branch reuse `PurchaseFlowApplied` independently; Pilot 5
 * has no such reuse goal, so a single stage keeps the cascade short
 * (2 Beings instead of 4). Pilot 5 is about Complex Convergence —
 * demonstrating how multiple Reasons can sit on the same Being — and
 * collapsing the trivial cascade here highlights that intent.
 */
#[Be(CheckoutSettled::class)]
final readonly class CheckoutPrepared
{
    public OrderEntity $order;
    public PurchaseFlowResult $totals;

    public function __construct(
        #[Input] public string $preOrderId,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] PurchaseFlowInterface $purchaseFlow,
    ) {
        $order = $orderQuery->byPreOrderId($preOrderId);
        if (! $order instanceof OrderEntity) {
            throw new PreOrderNotFoundException();
        }

        $this->order = $order;
        $this->totals = $purchaseFlow->apply($order);
    }
}
