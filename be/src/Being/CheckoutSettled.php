<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use DateTimeImmutable;
use DateTimeInterface;
use MyVendor\BeMart\Be\Final\CheckoutCompleted;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseTotals;
use MyVendor\BeMart\Be\Reason\Service\InventoryAllocatorInterface;
use MyVendor\BeMart\Be\Reason\Service\PreOrderClaimInterface;
use MyVendor\BeMart\Be\Reason\Provider\OrderNoProvider;
use MyVendor\BeMart\Be\Reason\Service\PaymentGatewayInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Stage 2 Being — pre-order claimed, inventory reserved, payment charged.
 *
 * Four Reasons converge on a single Being, executed in strict sequence:
 *
 *   1. `OrderNoProvider::get()` — issues the customer-facing order number.
 *      It runs first because it doubles as the claim token below.
 *   2. `PreOrderClaimInterface::claim()` — the concurrency arbiter. Stage 1
 *      only proves the pre-order WAS in PROCESSING when it was read; two
 *      requests can both hold that proof. The claim lets exactly one of
 *      them stamp its order number on the row, and reports the winner so
 *      the losers stop with `PreOrderAlreadyClaimedException` before
 *      anything irreversible happens. Without it a replayed or concurrent
 *      checkout charged the card twice, registered the line items twice
 *      and mailed two confirmations against one order row.
 *   3. `InventoryAllocatorInterface::allocate()` — decrements on-hand stock
 *      atomically. Throws `InsufficientStockException` if any line item
 *      exceeds the available count. No partial commits.
 *   4. `PaymentGatewayInterface::checkout()` — settles the payment with the
 *      gateway. Runs last, and only for the request that holds the claim,
 *      so we never charge for stock we cannot fulfill nor for an order
 *      another request is already completing. Throws
 *      `PaymentDeclinedException` on decline.
 *
 * Existence of this object proves: this request owns the completion AND
 * stock is reserved AND payment is captured AND an order number has been
 * allocated. The Final's job is to make these effects durable
 * (persist + mail + cart-clear).
 *
 * Residual: the claim bounds duplication, not partial failure. A crash
 * between the charge here and the writes in the Final still leaves a
 * charged card without line items, and the claim is not released — the
 * pre-order stays out of PROCESSING and the customer cannot retry. Making
 * that atomic needs the whole cascade inside one transaction with the
 * gateway call deferred, which is a larger change than this guard.
 */
#[Be(CheckoutCompleted::class)]
final readonly class CheckoutSettled
{
    public string $orderNo;
    public string $orderDate;
    public string $paymentDate;

    public function __construct(
        #[Input] public string $preOrderId,
        #[Input] public OrderEntity $order,
        #[Input] public PurchaseTotals $totals,
        #[Inject] OrderNoProvider $orderNumbers,
        #[Inject] PreOrderClaimInterface $claim,
        #[Inject] InventoryAllocatorInterface $inventory,
        #[Inject] PaymentGatewayInterface $gateway,
    ) {
        $orderNo = $orderNumbers->get();
        $claim->claim($preOrderId, $orderNo)->assertHeldBy($orderNo);

        $inventory->allocate($order);
        // Payment method is sourced from the persisted order, not from the
        // client request — see CheckoutInput docblock for the rationale.
        $gateway->checkout($preOrderId, $order->paymentMethodId, $totals->paymentTotal);

        $this->orderNo = $orderNo;
        $now = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
        $this->orderDate = $now;
        $this->paymentDate = $now;
    }
}
