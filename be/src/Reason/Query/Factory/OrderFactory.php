<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;

final class OrderFactory
{
    public function factory(
        string|null $preOrderId,
        int|string|null $customerId,
        int|string|null $paymentId,
        int|string $deliveryFeeTotal,
    ): OrderEntity {
        return new OrderEntity(
            $preOrderId ?? '',
            (string) ($customerId ?? ''),
            $paymentId === null ? 0 : (int) $paymentId,
            [],
            (int) $deliveryFeeTotal,
        );
    }
}
