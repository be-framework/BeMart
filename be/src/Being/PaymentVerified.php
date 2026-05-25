<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerifyResult;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseFlowResult;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Stage 3 Being — PaymentMethod::verify() result captured.
 *
 * Looks up the concrete PaymentMethod for $paymentMethodId via the factory and
 * runs verify() against the resolved pre-order. The result (success + errors)
 * drives the branching at the next stage.
 *
 * Existence of this object proves a verify call returned a definite answer
 * (success or failure with errors). It does NOT mean payment succeeded.
 *
 * Public surface carries totals forward so OrderConfirming can hand them to
 * PaymentSuccessCase on the happy path.
 */
#[Be(OrderConfirming::class)]
final readonly class PaymentVerified
{
    public PaymentVerifyResult $result;

    public function __construct(
        #[Input] public string $preOrderId,
        #[Input] public int $paymentMethodId,
        #[Input] OrderEntity $order,
        #[Input] public PurchaseFlowResult $totals,
        #[Inject] PaymentMethodFactoryInterface $paymentMethodFactory,
    ) {
        $method = $paymentMethodFactory->methodFor($paymentMethodId);
        $this->result = $method->verify($order);
    }
}
