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
            orderNo: $orderNo ?? '',
            customerId: (string) ($customerId ?? ''),
            message: $message ?? '',
            paymentMethod: $paymentMethod ?? '',
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
            shippings: $this->shippings($shippingsJson),
            mailHistories: $this->mailHistories($mailHistoriesJson),
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
                name01: (string) ($row['name01'] ?? ''),
                name02: (string) ($row['name02'] ?? ''),
                kana01: (string) ($row['kana01'] ?? ''),
                kana02: (string) ($row['kana02'] ?? ''),
                postalCode: (string) ($row['postalCode'] ?? ''),
                prefName: (string) ($row['prefName'] ?? ''),
                addr01: (string) ($row['addr01'] ?? ''),
                addr02: (string) ($row['addr02'] ?? ''),
                phoneNumber: (string) ($row['phoneNumber'] ?? ''),
                deliveryName: (string) ($row['deliveryName'] ?? ''),
                deliveryDate: (string) ($row['deliveryDate'] ?? ''),
                deliveryTime: (string) ($row['deliveryTime'] ?? ''),
                items: $this->items(isset($row['items']) ? $row['items'] : []),
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
                productCode: (string) ($row['productCode'] ?? ''),
                productName: (string) ($row['productName'] ?? ''),
                quantity: (int) ($row['quantity'] ?? 0),
                unitPrice: (int) ($row['unitPrice'] ?? 0),
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
                sendDate: (string) ($row['sendDate'] ?? ''),
                mailSubject: (string) ($row['mailSubject'] ?? ''),
                mailBody: (string) ($row['mailBody'] ?? ''),
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
