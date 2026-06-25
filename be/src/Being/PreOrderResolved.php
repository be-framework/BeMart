<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryFeeEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
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
 *
 * Delivery-method selection (alps doConfirmOrder): when the customer picked a
 * 配送方法 on /shopping, `deliveryId` arrives here. We resolve its base 送料
 * via {@see DeliveryStorageInterface::baseFee} and rebuild the resolved order
 * with that deliveryFeeTotal so PurchaseFlowApplied computes totals against
 * the CHOSEN fee. An empty `deliveryId` keeps the pre-order's persisted fee —
 * the existing guest/member checkout (no explicit pick) does NOT regress.
 */
#[Be(PurchaseFlowApplied::class)]
final readonly class PreOrderResolved
{
    public OrderEntity $order;

    public function __construct(
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] DeliveryStorageInterface $deliveryStorage,
        #[Input] public string $preOrderId,
        #[Input] public int $paymentMethodId,
        #[Input] public string $deliveryId = '',
        #[Input] public string $deliveryDate = '',
        #[Input] public string $deliveryTime = '',
    ) {
        $order = $orderQuery->byPreOrderId($preOrderId);
        if (! $order instanceof OrderEntity) {
            throw new PreOrderNotFoundException();
        }

        $this->order = $this->applyChosenDeliveryFee($order, $deliveryStorage);
    }

    private function applyChosenDeliveryFee(
        OrderEntity $order,
        DeliveryStorageInterface $deliveryStorage,
    ): OrderEntity {
        if ($this->deliveryId === '') {
            return $order;
        }

        $fee = $deliveryStorage->baseFee($this->deliveryId);
        if (! $fee instanceof DeliveryFeeEntity) {
            return $order;
        }

        return new OrderEntity(
            preOrderId: $order->preOrderId,
            customerId: $order->customerId,
            paymentMethodId: $order->paymentMethodId,
            items: $order->items,
            deliveryFeeTotal: $fee->fee,
            customerSnapshot: $order->customerSnapshot,
        );
    }
}
