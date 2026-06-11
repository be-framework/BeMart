<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;

use JsonException;

use function is_array;
use function is_int;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

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
        string|null $customerSnapshotJson = null,
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
            $this->customerSnapshot($customerSnapshotJson),
        );
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
