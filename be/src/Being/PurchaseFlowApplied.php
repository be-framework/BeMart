<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseTotals;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Service\PurchaseFlowInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Stage 2 Being — PurchaseFlow totals applied.
 *
 * Runs the shopping flow on the resolved pre-order to produce the totals
 * exposed by the ShoppingConfirm state: subtotal / deliveryFeeTotal / charge
 * / discount / tax / total / paymentTotal / addPoint / usePoint.
 *
 * Public surface carries the Input scalars + $order forward so the downstream
 * chain can keep referencing them.
 *
 * Delivery-method selection (alps doConfirmOrder): the resolved $order already
 * carries the CHOSEN delivery's 送料 ({@see PreOrderResolved} rebuilt it from
 * deliveryId), so $purchaseFlow->apply recomputes the totals against that fee.
 * When a delivery was chosen we PERSIST the recomputed totals back onto the
 * PROCESSING pre-order row, so the later doCheckout — which re-reads the order
 * row — settles the order with the chosen 送料. An empty deliveryId skips the
 * write, keeping the existing checkout (which uses the pre-order's persisted
 * fee) unchanged.
 */
#[Be(PaymentVerified::class)]
final readonly class PurchaseFlowApplied
{
    public PurchaseTotals $totals;

    public function __construct(
        #[Input] public string $preOrderId,
        #[Input] public int $paymentMethodId,
        #[Input] public OrderEntity $order,
        #[Inject] PurchaseFlowInterface $purchaseFlow,
        #[Inject] OrderCommandInterface $orderCommand,
        #[Input] public string $deliveryId = '',
    ) {
        $this->totals = $purchaseFlow->apply($order);

        if ($deliveryId !== '') {
            $this->persistChosenTotals($order, $this->totals, $orderCommand);
        }
    }

    private function persistChosenTotals(
        OrderEntity $order,
        PurchaseTotals $totals,
        OrderCommandInterface $orderCommand,
    ): void {
        $orderCommand->register(new FinalizedOrderEntity(
            orderNo: $order->preOrderId,
            preOrderId: $order->preOrderId,
            customerId: $order->customerId,
            paymentMethodId: $order->paymentMethodId,
            subtotal: $totals->subtotal,
            deliveryFeeTotal: $totals->deliveryFeeTotal,
            charge: $totals->charge,
            discount: $totals->discount,
            tax: $totals->tax,
            total: $totals->total,
            paymentTotal: $totals->paymentTotal,
            addPoint: $totals->addPoint,
            usePoint: $totals->usePoint,
            orderStatus: FinalizedOrderEntity::STATUS_PROCESSING,
            orderDate: '',
            paymentDate: '',
            customerSnapshot: $order->customerSnapshot,
        ));
    }
}
