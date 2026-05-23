<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseTotals;
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
    ) {
        $this->totals = $purchaseFlow->apply($order);
    }
}
