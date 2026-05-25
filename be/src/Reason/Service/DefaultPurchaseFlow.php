<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseTotals;
use Override;

use function array_map;
use function array_sum;

final class DefaultPurchaseFlow implements PurchaseFlowInterface
{
    private const TAX_RATE = 0.10;
    private const POINT_RATE = 0.01;

    #[Override]
    public function apply(OrderEntity $preOrder): PurchaseTotals
    {
        $subtotal = (int) array_sum(array_map(
            static fn (CartItemEntity $item): int => $item->price * $item->quantity,
            $preOrder->items,
        ));
        $tax = (int) ((float) $subtotal * self::TAX_RATE);
        $total = $subtotal + $tax + $preOrder->deliveryFeeTotal;

        return new PurchaseTotals(
            subtotal: $subtotal,
            deliveryFeeTotal: $preOrder->deliveryFeeTotal,
            charge: 0,
            discount: 0,
            tax: $tax,
            total: $total,
            paymentTotal: $total,
            addPoint: (int) ((float) $total * self::POINT_RATE),
            usePoint: 0,
        );
    }
}
