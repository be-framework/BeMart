<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedPreOrderAccessException;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseTotals;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\PurchaseFlowInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Stage 1 Being — pre-order resolved, ownership verified, totals applied.
 *
 * Combines three Reasons in one Being (Pilot 5 Cascade Multi-Reason):
 *
 *   1. `OrderQueryInterface` — proves a pre-order with
 *      orderStatus=PROCESSING(8) exists for the given preOrderId. Throws
 *      `PreOrderNotFoundException` on miss.
 *   2. `SessionInterface` — proves the current request's customer owns
 *      the pre-order. Phase B Slice 6 (Pilot 5 F-1 AUTHZ): compare
 *      `$session->customerId()` against `$order->customerId`; mismatch
 *      (including anonymous sessions) throws
 *      `UnauthorizedPreOrderAccessException`.
 *   3. `PurchaseFlowInterface` — runs the EC-CUBE shopping flow against the
 *      pre-order to compute the totals (subtotal / tax / etc.). The output
 *      is the same shape as ShoppingConfirm and matches Pilot 3's
 *      `PurchaseFlowApplied`.
 *
 * Existence of this object guarantees: pre-order exists AND the requester
 * owns it AND totals have been computed. Downstream Beings can rely on
 * all three without re-validating.
 *
 * The merged-stage choice (vs. Pilot 3's separate `PreOrderResolved` +
 * `PurchaseFlowApplied`) is deliberate. Pilot 3 split them to let the
 * verify-failure branch reuse `PurchaseFlowApplied` independently; Pilot 5
 * has no such reuse goal, so a single stage keeps the cascade short
 * (2 Beings instead of 4). Pilot 5 is about Complex Convergence —
 * demonstrating how multiple Reasons can sit on the same Being — and
 * collapsing the trivial cascade here highlights that intent.
 *
 * Ordering note: ownership check runs *after* existence (so we don't leak
 * existence information through differing error codes) and *before*
 * PurchaseFlow (so we don't burn compute on an unauthorized request).
 */
#[Be(CheckoutSettled::class)]
final readonly class CheckoutPrepared
{
    public OrderEntity $order;
    public PurchaseTotals $totals;

    public function __construct(
        #[Input] public string $preOrderId,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] SessionInterface $session,
        #[Inject] PurchaseFlowInterface $purchaseFlow,
    ) {
        $order = $orderQuery->byPreOrderId($preOrderId);
        if (! $order instanceof OrderEntity) {
            throw new PreOrderNotFoundException();
        }

        if ($session->customerId() !== $order->customerId) {
            throw new UnauthorizedPreOrderAccessException();
        }

        $this->order = $order;
        $this->totals = $purchaseFlow->apply($order);
    }
}
