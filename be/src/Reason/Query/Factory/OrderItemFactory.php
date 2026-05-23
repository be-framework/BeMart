<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;

final class OrderItemFactory
{
    public function factory(
        string $orderNo,
        string|null $productCode,
        string $productName,
        int|string $quantity,
        int|string $price,
    ): OrderItemEntity {
        return new OrderItemEntity(
            orderNo: $orderNo,
            productCode: $productCode ?? '',
            productName: $productName,
            quantity: (int) $quantity,
            unitPrice: (int) $price,
        );
    }
}
