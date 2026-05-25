<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Factory;

use JsonException;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryMailEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryShippingEntity;

use function array_values;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class OrderHistoryFactory
{
    public function factory(
        string|null $orderNo,
        int|string|null $customerId,
        string|null $message,
        string|null $paymentMethod,
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
        string|null $shippingsJson,
        string|null $mailHistoriesJson,
    ): OrderHistoryEntity {
        return new OrderHistoryEntity(
            $orderNo ?? '',
            (string) ($customerId ?? ''),
            $message ?? '',
            $paymentMethod ?? '',
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
            $this->shippings($shippingsJson),
            $this->mailHistories($mailHistoriesJson),
        );
    }

    /** @return list<OrderHistoryShippingEntity> */
    private function shippings(string|null $json): array
    {
        $decoded = $this->decodeList($json);
        $out = [];
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = new OrderHistoryShippingEntity(
                (string) ($row['name01'] ?? ''),
                (string) ($row['name02'] ?? ''),
                (string) ($row['kana01'] ?? ''),
                (string) ($row['kana02'] ?? ''),
                (string) ($row['postalCode'] ?? ''),
                (string) ($row['prefName'] ?? ''),
                (string) ($row['addr01'] ?? ''),
                (string) ($row['addr02'] ?? ''),
                (string) ($row['phoneNumber'] ?? ''),
                (string) ($row['deliveryName'] ?? ''),
                (string) ($row['deliveryDate'] ?? ''),
                (string) ($row['deliveryTime'] ?? ''),
                $this->items(isset($row['items']) ? $row['items'] : []),
            );
        }

        return $out;
    }

    /**
     * @param mixed $rows
     * @return list<OrderHistoryItemEntity>
     */
    private function items(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }
        $out = [];
        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = new OrderHistoryItemEntity(
                (string) ($row['productCode'] ?? ''),
                (string) ($row['productName'] ?? ''),
                (int) ($row['quantity'] ?? 0),
                (int) ($row['unitPrice'] ?? 0),
            );
        }

        return $out;
    }

    /** @return list<OrderHistoryMailEntity> */
    private function mailHistories(string|null $json): array
    {
        $out = [];
        foreach ($this->decodeList($json) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = new OrderHistoryMailEntity(
                (string) ($row['sendDate'] ?? ''),
                (string) ($row['mailSubject'] ?? ''),
                (string) ($row['mailBody'] ?? ''),
            );
        }

        return $out;
    }

    /** @return list<mixed> */
    private function decodeList(string|null $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? array_values($decoded) : [];
    }
}
