<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use JsonException;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;

use function array_values;
use function is_array;
use function json_decode;
use function is_int;
use function is_string;

use const JSON_THROW_ON_ERROR;

final class OrderFactory
{
    public function factory(
        string|null $preOrderId,
        int|string|null $customerId,
        int|string|null $paymentId,
        int|string $deliveryFeeTotal,
        string|null $itemsJson = null,
        string|null $customerSnapshotJson = null,
    ): OrderEntity {
        return new OrderEntity(
            $preOrderId ?? '',
            (string) ($customerId ?? ''),
            $paymentId === null ? 0 : (int) $paymentId,
            $this->items($itemsJson),
            (int) $deliveryFeeTotal,
            $this->customerSnapshot($customerSnapshotJson),
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

    /**
     * @return array{}|array{
     *   name01: string, name02: string, kana01: string|null, kana02: string|null,
     *   companyName: string|null, email: string, phoneNumber: string|null,
     *   postalCode: string|null, pref: int|null, addr01: string|null, addr02: string|null
     * }
     */
    private function customerSnapshot(string|null $json): array
    {
        $decoded = $this->decodeArray($json);
        if ($decoded === []) {
            return [];
        }

        return [
            'name01' => self::stringValue($decoded['name01'] ?? null),
            'name02' => self::stringValue($decoded['name02'] ?? null),
            'kana01' => self::nullableString($decoded['kana01'] ?? null),
            'kana02' => self::nullableString($decoded['kana02'] ?? null),
            'companyName' => self::nullableString($decoded['companyName'] ?? null),
            'email' => self::stringValue($decoded['email'] ?? null),
            'phoneNumber' => self::nullableString($decoded['phoneNumber'] ?? null),
            'postalCode' => self::nullableString($decoded['postalCode'] ?? null),
            'pref' => self::nullableInt($decoded['pref'] ?? null),
            'addr01' => self::nullableString($decoded['addr01'] ?? null),
            'addr02' => self::nullableString($decoded['addr02'] ?? null),
        ];
    }

    /** @return array<string, mixed> */
    private function decodeArray(string|null $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private static function stringValue(mixed $value): string
    {
        return is_string($value) || is_int($value) ? (string) $value : '';
    }

    private static function nullableString(mixed $value): string|null
    {
        if ($value === null) {
            return null;
        }

        return self::stringValue($value);
    }

    private static function nullableInt(mixed $value): int|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
