<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\OrderConfirmFailed;
use MyVendor\BeMart\Be\Final\OrderConfirmed;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerifyResult;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseFlowResult;
use MyVendor\BeMart\Be\Reason\PaymentFailureCase;
use MyVendor\BeMart\Be\Reason\PaymentSuccessCase;
use Ray\InputQuery\Attribute\Input;

/**
 * Stage 4 Being — Branching point.
 *
 * Reads the verify $result and the computed $totals from the upstream chain
 * and decides which Final wins by constructing the matching Case as the typed
 * $being discriminator. The Be Framework's BecomingType then selects
 * OrderConfirmed (PaymentSuccessCase) or OrderConfirmFailed (PaymentFailureCase)
 * by matching the type against each Final's `#[Input] <Case> $being` parameter.
 *
 * Phase 3 enrichment — the resolved pre-order $order is forwarded so the
 * happy-path Final (OrderConfirmed) can compose the confirm-screen
 * order-detail projection (customer info + line items) off it.
 */
#[Be([OrderConfirmed::class, OrderConfirmFailed::class])]
final readonly class OrderConfirming
{
    /** Typed discriminator: framework picks the Final by case type. */
    public PaymentSuccessCase|PaymentFailureCase $being;

    public function __construct(
        #[Input] public string $preOrderId,
        #[Input] public int $paymentMethodId,
        #[Input] public OrderEntity $order,
        #[Input] PurchaseFlowResult $totals,
        #[Input] PaymentVerifyResult $result,
    ) {
        $this->being = $result->success
            ? new PaymentSuccessCase($totals)
            : new PaymentFailureCase($result->errors);
    }
}
