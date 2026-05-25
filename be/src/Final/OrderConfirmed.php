<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\PaymentSuccessCase;
use Ray\InputQuery\Attribute\Input;

/**
 * Final — proof that confirm() succeeded and the ShoppingConfirm state is
 * ready for the customer.
 *
 * Selected by the Be Framework when OrderConfirming's $being discriminator
 * is a PaymentSuccessCase. All ShoppingConfirm scalars are read off that
 * Case — the Final delegates to it (medical-triage Case-class pattern).
 *
 * The public surface mirrors ALPS ShoppingConfirm descriptors.
 */
final readonly class OrderConfirmed
{
    public string $preOrderId;
    public int $paymentMethodId;
    public int $subtotal;
    public int $deliveryFeeTotal;
    public int $charge;
    public int $discount;
    public int $tax;
    public int $total;
    public int $paymentTotal;
    public int $addPoint;
    public int $usePoint;

    public function __construct(
        #[Input] public PaymentSuccessCase $being,
        #[Input] string $preOrderId,
        #[Input] int $paymentMethodId,
    ) {
        $totals = $being->totals;

        $this->preOrderId = $preOrderId;
        $this->paymentMethodId = $paymentMethodId;
        $this->subtotal = $totals->subtotal;
        $this->deliveryFeeTotal = $totals->deliveryFeeTotal;
        $this->charge = $totals->charge;
        $this->discount = $totals->discount;
        $this->tax = $totals->tax;
        $this->total = $totals->total;
        $this->paymentTotal = $totals->paymentTotal;
        $this->addPoint = $totals->addPoint;
        $this->usePoint = $totals->usePoint;
    }
}
