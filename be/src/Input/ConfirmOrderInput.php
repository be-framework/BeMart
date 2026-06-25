<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Being\PreOrderResolved;

/**
 * Input for doConfirmOrder — confirm the pre-order before checkout.
 *
 * Linear Cascade + Branching:
 *
 *   ConfirmOrderInput
 *     → PreOrderResolved   (Stage 1 — pre-order existence proved)
 *     → PurchaseFlowApplied (Stage 2 — totals computed)
 *     → PaymentVerified    (Stage 3 — verify() called)
 *     → OrderConfirming    (Stage 4 — Branching)
 *         #[Be([OrderConfirmed, OrderConfirmFailed])]
 *     → OrderConfirmed     (verify success → ShoppingConfirm state)
 *     | OrderConfirmFailed (verify failure → ShoppingError state)
 *
 * The original design aimed for a Cascade Diamond (PreOrderResolved as apex
 * shared by PurchaseFlowApplied and PaymentVerified). The current Be Framework
 * resolves `#[Inject]` of a Being class through Ray.Di, which does not honor
 * the injected target's `#[Input]` parameters — so a Diamond whose apex needs
 * Input data is not expressible. The chain is therefore linearised and each
 * stage forwards the Input scalars in its public surface. See
 * docs/be-adoption-evaluation.md §6 for the recorded finding.
 *
 * @link https://schema.org/ConfirmAction
 */
#[Be(PreOrderResolved::class)]
final readonly class ConfirmOrderInput
{
    /**
     * Phase B Slice 9: preOrderId + paymentMethodId come from the HTTP
     * confirm form. The doConfirmOrder contract (alps.json) also carries
     * the delivery-method selection — deliveryId / deliveryDate /
     * deliveryTime — chosen on /shopping. deliveryId drives the
     * deliveryFeeTotal fix-up in {@see PreOrderResolved}; an empty
     * deliveryId keeps the pre-order's persisted fee (guest/member
     * checkout without an explicit pick does NOT regress). deliveryDate /
     * deliveryTime are the shipping slot labels, forwarded for the
     * confirm screen and the persisted shipping snapshot.
     *
     * @psalm-taint-source input $preOrderId
     * @psalm-taint-source input $paymentMethodId
     * @psalm-taint-source input $deliveryId
     * @psalm-taint-source input $deliveryDate
     * @psalm-taint-source input $deliveryTime
     */
    public function __construct(
        public string $preOrderId,
        public int $paymentMethodId,
        public string $deliveryId = '',
        public string $deliveryDate = '',
        public string $deliveryTime = '',
    ) {
    }
}
