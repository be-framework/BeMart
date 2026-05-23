<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;

final class FinalizedOrderFactory
{
    public function factory(
        string|null $orderNo,
        string|null $preOrderId,
        int|string|null $customerId,
        int|string|null $paymentId,
        int|string $subtotal,
        int|string $deliveryFeeTotal,
        int|string $charge,
        int|string $discount,
        int|string $tax,
        int|string $total,
        int|string $paymentTotal,
        int|string $addPoint,
        int|string $usePoint,
        int|string|null $orderStatusId,
        string|null $orderDate,
        string|null $paymentDate,
    ): FinalizedOrderEntity {
        return new FinalizedOrderEntity(
            orderNo: $orderNo ?? '',
            preOrderId: $preOrderId ?? '',
            customerId: (string) ($customerId ?? ''),
            paymentMethodId: $paymentId === null ? 0 : (int) $paymentId,
            subtotal: (int) $subtotal,
            deliveryFeeTotal: (int) $deliveryFeeTotal,
            charge: (int) $charge,
            discount: (int) $discount,
            tax: (int) $tax,
            total: (int) $total,
            paymentTotal: (int) $paymentTotal,
            addPoint: (int) $addPoint,
            usePoint: (int) $usePoint,
            orderStatus: $orderStatusId === null ? 0 : (int) $orderStatusId,
            orderDate: $orderDate ?? '',
            paymentDate: $paymentDate ?? '',
        );
    }
}
