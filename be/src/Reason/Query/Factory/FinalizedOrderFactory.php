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
            $orderNo ?? '',
            $preOrderId ?? '',
            (string) ($customerId ?? ''),
            $paymentId === null ? 0 : (int) $paymentId,
            (int) $subtotal,
            (int) $deliveryFeeTotal,
            (int) $charge,
            (int) $discount,
            (int) $tax,
            (int) $total,
            (int) $paymentTotal,
            (int) $addPoint,
            (int) $usePoint,
            $orderStatusId === null ? 0 : (int) $orderStatusId,
            $orderDate ?? '',
            $paymentDate ?? '',
        );
    }
}
