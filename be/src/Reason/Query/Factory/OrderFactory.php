<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use JsonException;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;

use function array_values;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class OrderFactory
{
    public function factory(
        string|null $preOrderId,
        int|string|null $customerId,
        int|string|null $paymentId,
        int|string $deliveryFeeTotal,
        string|null $itemsJson = null,
    ): OrderEntity {
        return new OrderEntity(
            $preOrderId ?? '',
            (string) ($customerId ?? ''),
            $paymentId === null ? 0 : (int) $paymentId,
            $this->items($itemsJson),
            (int) $deliveryFeeTotal,
        );
    }

    /** @return list<CartItemEntity> */
    private function items(string|null $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach (array_values($decoded) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $items[] = new CartItemEntity(
                productCode: (string) ($row['productCode'] ?? ''),
                quantity: (int) ($row['quantity'] ?? 0),
                price: (int) ($row['price'] ?? 0),
                productName: (string) ($row['productName'] ?? ''),
            );
        }

        return $items;
    }
}
