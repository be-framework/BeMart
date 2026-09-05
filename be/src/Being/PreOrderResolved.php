<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedPreOrderAccessException;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Stage 1 Being — the pre-order Order entity, loaded and owned.
 *
 * EC-CUBE's ShoppingController::confirm() rejects requests when
 * `OrderHelper::getPurchaseProcessingOrder($preOrderId)` returns null. We
 * model that rejection as a fail-fast existence: the only way an instance
 * of this class can exist is if a PROCESSING(8) order really was found.
 *
 * Existence alone is not enough — a pre-order id carries another shopper's
 * checkout PII, so `CustomerSession` proves the requester owns it. Same rule
 * as {@see CheckoutPrepared}: a mismatch, an anonymous session included,
 * throws `UnauthorizedPreOrderAccessException`. The carve-out is non-member
 * checkout, where an anonymous session resolves a guest pre-order
 * (`customerId === ''`).
 *
 * Ownership runs after existence so the two rejections cannot be played
 * against each other to probe which pre-order ids exist.
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
        #[Inject] CustomerSession $session,
    ) {
        $order = $orderQuery->byPreOrderId($preOrderId);
        if (! $order instanceof OrderEntity) {
            throw new PreOrderNotFoundException();
        }

        $guestPreOrder = $session->customerId === null && $order->customerId === '';
        if (! $guestPreOrder && $session->customerId !== $order->customerId) {
            throw new UnauthorizedPreOrderAccessException();
        }

        $this->order = $order;
    }
}
