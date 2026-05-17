<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Stage 1 Being — the pre-order Order entity, loaded.
 *
 * EC-CUBE's ShoppingController::confirm() rejects requests when
 * `OrderHelper::getPurchaseProcessingOrder($preOrderId)` returns null. We
 * model that rejection as a fail-fast existence: the only way an instance
 * of this class can exist is if a PROCESSING(8) order really was found.
 *
 * Public surface carries the resolved $order plus the Input scalars so the
 * downstream chain (PurchaseFlowApplied → PaymentVerified → OrderConfirming)
 * can keep them as `#[Input]`.
 */
#[Be(PurchaseFlowApplied::class)]
final readonly class PreOrderResolved
{
    public OrderEntity $order;

    public function __construct(
        #[Input] public string $preOrderId,
        #[Input] public int $paymentMethodId,
        #[Inject] OrderQueryInterface $orderQuery,
    ) {
        $order = $orderQuery->byPreOrderId($preOrderId);
        if (! $order instanceof OrderEntity) {
            throw new PreOrderNotFoundException();
        }

        $this->order = $order;
    }
}
