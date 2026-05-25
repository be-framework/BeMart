<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use DateTimeImmutable;
use DateTimeInterface;
use MyVendor\BeMart\Be\Final\CheckoutCompleted;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseFlowResult;
use MyVendor\BeMart\Be\Reason\Service\InventoryAllocatorInterface;
use MyVendor\BeMart\Be\Reason\Service\OrderNumberGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentGatewayInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Stage 2 Being — inventory reserved, payment charged, order number issued.
 *
 * Three Reasons converge on a single Being, executed in strict sequence:
 *
 *   1. `InventoryAllocatorInterface::allocate()` — decrements on-hand stock
 *      atomically. Throws `InsufficientStockException` if any line item
 *      exceeds the available count. No partial commits.
 *   2. `PaymentGatewayInterface::checkout()` — settles the payment with the
 *      gateway. Runs ONLY after inventory has been reserved (so we never
 *      charge for stock we cannot fulfill). Throws
 *      `PaymentDeclinedException` on decline.
 *   3. `OrderNumberGeneratorInterface::generate()` — issues the customer-
 *      facing order number. Runs last because there is no point allocating
 *      a number for a checkout that already failed.
 *
 * Existence of this object proves: stock is reserved AND payment is
 * captured AND an order number has been allocated. The Final's job is to
 * make these effects durable (persist + mail + cart-clear).
 *
 * Note: this Being executes side effects in its constructor (gateway
 * charge, stock decrement). Failures in the Final after this point leave
 * the system in a state where the customer's card has been charged but
 * the order row is missing. Production Phase 2 will move the cascade
 * inside a single DB transaction with the gateway call placed last under
 * `register_shutdown_function()`; for Pilot 5 the in-memory fakes make
 * this acceptable.
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
        #[Input] public PurchaseFlowResult $totals,
        #[Inject] InventoryAllocatorInterface $inventory,
        #[Inject] PaymentGatewayInterface $gateway,
        #[Inject] OrderNumberGeneratorInterface $numbers,
    ) {
        $inventory->allocate($order);
        // Payment method is sourced from the persisted order, not from the
        // client request — see CheckoutInput docblock for the rationale.
        $gateway->checkout($preOrderId, $order->paymentMethodId, $totals->paymentTotal);

        $this->orderNo = $numbers->generate();
        $now = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
        $this->orderDate = $now;
        $this->paymentDate = $now;
    }
}
