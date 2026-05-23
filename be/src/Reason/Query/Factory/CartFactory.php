<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use JsonException;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;

use function array_values;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class CartFactory
{
    public function factory(
        string $cartKey,
        int|string|null $saleTypeId,
        string|null $saleTypeName,
        string|null $itemsJson,
        int|string $totalPrice,
        int|string $deliveryFeeTotal,
        string|null $preOrderId,
    ): CartEntity {
        return new CartEntity(
            cartKey: $cartKey,
            saleTypeId: $saleTypeId === null ? 0 : (int) $saleTypeId,
            saleTypeName: $saleTypeName ?? '',
            items: $this->items($itemsJson),
            totalPrice: (int) $totalPrice,
            deliveryFeeTotal: (int) $deliveryFeeTotal,
            preOrderId: $preOrderId ?? '',
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
                productClassId: (int) ($row['productClassId'] ?? 0),
                productId: (int) ($row['productId'] ?? 0),
                productName: (string) ($row['productName'] ?? ''),
                mainImage: isset($row['mainImage']) ? (string) $row['mainImage'] : null,
                classCategoryName1: isset($row['classCategoryName1']) ? (string) $row['classCategoryName1'] : null,
                className1: isset($row['className1']) ? (string) $row['className1'] : null,
                classCategoryName2: isset($row['classCategoryName2']) ? (string) $row['classCategoryName2'] : null,
                className2: isset($row['className2']) ? (string) $row['className2'] : null,
            );
        }

        return $items;
    }
}
