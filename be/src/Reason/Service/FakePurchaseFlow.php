<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseFlowResult;
use Override;

use function array_map;
use function array_sum;

/**
 * Pilot 3 deterministic fake of EC-CUBE PurchaseFlow.
 *
 * Computes the totals the same way as the shopping flow at a slimmed-down
 * level: 10% tax on subtotal, deliveryFee passed through from the order,
 * no point processor and no payment surcharge for the Pilot scope.
 *
 * Production binding wraps Eccube\Service\PurchaseFlow\PurchaseFlow.
 */
final class FakePurchaseFlow implements PurchaseFlowInterface
{
    private const TAX_RATE = 0.10;
    private const POINT_RATE = 0.01;

    #[Override]
    public function apply(OrderEntity $preOrder): PurchaseFlowResult
    {
        $subtotal = (int) array_sum(array_map(
            static fn (CartItemEntity $i): int => $i->price * $i->quantity,
            $preOrder->items,
        ));

        $tax = (int) ($subtotal * self::TAX_RATE);
        $deliveryFeeTotal = $preOrder->deliveryFeeTotal;
        $charge = 0;
        $discount = 0;
        $total = $subtotal + $tax + $deliveryFeeTotal + $charge - $discount;
        $paymentTotal = $total;
        $addPoint = (int) ($total * self::POINT_RATE);
        $usePoint = 0;

        return new PurchaseFlowResult(
            subtotal: $subtotal,
            deliveryFeeTotal: $deliveryFeeTotal,
            charge: $charge,
            discount: $discount,
            tax: $tax,
            total: $total,
            paymentTotal: $paymentTotal,
            addPoint: $addPoint,
            usePoint: $usePoint,
        );
    }
}
