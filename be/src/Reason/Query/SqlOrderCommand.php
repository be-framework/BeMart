<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use Override;

use function ctype_digit;

final class SqlOrderCommand implements OrderCommandInterface
{
    private const DISCRIMINATOR = 'order';
    private const PLACEHOLDER_NAME = '-';

    public function __construct(private readonly InternalDbQueryInterface $db) {}

    #[Override]
    public function register(FinalizedOrderEntity $order): void
    {
        if ($this->preOrderRowExists($order->preOrderId)) {
            $this->db->order_promote_pre_order(
                orderNo: $order->orderNo,
                preOrderId: $order->preOrderId === '' ? null : $order->preOrderId,
                customerId: $this->customerId($order->customerId),
                paymentId: $this->paymentId($order->paymentMethodId),
                subtotal: $order->subtotal,
                deliveryFeeTotal: $order->deliveryFeeTotal,
                charge: $order->charge,
                discount: $order->discount,
                tax: $order->tax,
                total: $order->total,
                paymentTotal: $order->paymentTotal,
                addPoint: $order->addPoint,
                usePoint: $order->usePoint,
                orderStatus: $order->orderStatus,
                orderDate: $this->normalizeDateTime($order->orderDate),
                paymentDate: $this->normalizeDateTime($order->paymentDate),
            );

            return;
        }

        $this->db->order_insert(
            orderNo: $order->orderNo,
            preOrderId: $order->preOrderId === '' ? null : $order->preOrderId,
            customerId: $this->customerId($order->customerId),
            paymentId: $this->paymentId($order->paymentMethodId),
            name01: self::PLACEHOLDER_NAME,
            name02: self::PLACEHOLDER_NAME,
            subtotal: $order->subtotal,
            deliveryFeeTotal: $order->deliveryFeeTotal,
            charge: $order->charge,
            discount: $order->discount,
            tax: $order->tax,
            total: $order->total,
            paymentTotal: $order->paymentTotal,
            addPoint: $order->addPoint,
            usePoint: $order->usePoint,
            orderStatus: $order->orderStatus,
            orderDate: $this->normalizeDateTime($order->orderDate),
            paymentDate: $this->normalizeDateTime($order->paymentDate),
            discriminator: self::DISCRIMINATOR,
        );
    }

    #[Override]
    public function update(FinalizedOrderEntity $order): void
    {
        $this->db->order_update(
            orderNo: $order->orderNo,
            customerId: $this->customerId($order->customerId),
            paymentId: $this->paymentId($order->paymentMethodId),
            subtotal: $order->subtotal,
            deliveryFeeTotal: $order->deliveryFeeTotal,
            charge: $order->charge,
            discount: $order->discount,
            tax: $order->tax,
            total: $order->total,
            paymentTotal: $order->paymentTotal,
            addPoint: $order->addPoint,
            usePoint: $order->usePoint,
            orderStatus: $order->orderStatus,
            orderDate: $this->normalizeDateTime($order->orderDate),
            paymentDate: $this->normalizeDateTime($order->paymentDate),
        );
    }

    #[Override]
    public function updateStatus(string $orderNo, int $newStatus): void
    {
        $this->db->order_update_status(orderNo: $orderNo, status: $newStatus);
    }

    private function preOrderRowExists(string $preOrderId): bool
    {
        if ($preOrderId === '') {
            return false;
        }

        return $this->db->order_pre_order_exists(preOrderId: $preOrderId) !== null;
    }

    private function customerId(string $customerId): int|null
    {
        return ctype_digit($customerId) ? (int) $customerId : null;
    }

    private function paymentId(int $paymentMethodId): int|null
    {
        return $paymentMethodId > 0 ? $paymentMethodId : null;
    }

    private function normalizeDateTime(string $value): string|null
    {
        if ($value === '') {
            return null;
        }

        try {
            $dt = new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }

        return $dt->setTimezone(new DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s');
    }
}
