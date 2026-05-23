<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use MyVendor\BeMart\Be\Reason\Query\Result\TrackingNumberResult;
use Override;

final class SqlShippingAddressStorage implements ShippingAddressStorageInterface
{
    private const DISCRIMINATOR = 'shipping';

    public function __construct(private readonly InternalDbQueryInterface $db) {}

    #[Override]
    public function getByOrderNo(string $orderNo): ShippingAddressEntity|null
    {
        $orderId = $this->orderIdByOrderNo($orderNo);
        if ($orderId === null) {
            return null;
        }

        $row = $this->db->shipping_get_by_order_id(orderId: $orderId);

        return $row === null ? null : $this->hydrate($orderNo, $row);
    }

    #[Override]
    public function put(ShippingAddressEntity $address): void
    {
        $orderId = $this->orderIdByOrderNo($address->orderNo);
        if ($orderId === null) {
            return;
        }

        $existingId = $this->firstShippingId($orderId);
        if ($existingId !== null) {
            $this->db->shipping_update(
                id: $existingId,
                prefId: $address->pref === 0 ? null : $address->pref,
                name01: $address->name01,
                name02: $address->name02,
                postalCode: $address->postalCode,
                addr01: $address->addr01,
                addr02: $address->addr02,
                phoneNumber: $address->phoneNumber,
            );

            return;
        }

        $this->db->shipping_insert(
            orderId: $orderId,
            prefId: $address->pref === 0 ? null : $address->pref,
            name01: $address->name01,
            name02: $address->name02,
            postalCode: $address->postalCode,
            addr01: $address->addr01,
            addr02: $address->addr02,
            phoneNumber: $address->phoneNumber,
            discriminator: self::DISCRIMINATOR,
        );
    }

    /** @return list<ShippingAddressEntity> */
    #[Override]
    public function listAll(): array
    {
        return array_map(
            fn (array $row): ShippingAddressEntity => $this->hydrate((string) $row['order_no'], $row),
            $this->db->shipping_list_all(),
        );
    }

    #[Override]
    public function updateTrackingNumber(string $orderNo, string $trackingNumber): void
    {
        $orderId = $this->orderIdByOrderNo($orderNo);
        if ($orderId === null) {
            return;
        }

        $existingId = $this->firstShippingId($orderId);
        if ($existingId !== null) {
            $this->db->shipping_update_tracking(id: $existingId, trackingNumber: $trackingNumber);

            return;
        }

        $this->db->shipping_insert_tracking(orderId: $orderId, name01: '', name02: '', trackingNumber: $trackingNumber, discriminator: self::DISCRIMINATOR);
    }

    #[Override]
    public function trackingNumberByOrderNo(string $orderNo): TrackingNumberResult
    {
        $orderId = $this->orderIdByOrderNo($orderNo);
        if ($orderId === null) {
            return new TrackingNumberResult(null);
        }

        $row = $this->db->shipping_tracking_by_order_id(orderId: $orderId);
        if ($row === null || $row['tracking_number'] === null) {
            return new TrackingNumberResult(null);
        }

        return new TrackingNumberResult((string) $row['tracking_number']);
    }

    private function orderIdByOrderNo(string $orderNo): int|null
    {
        $row = $this->db->shipping_order_id_by_order_no(orderNo: $orderNo);

        return $row === null ? null : (int) $row['id'];
    }

    private function firstShippingId(int $orderId): int|null
    {
        $row = $this->db->shipping_first_id_by_order_id(orderId: $orderId);

        return $row === null ? null : (int) $row['id'];
    }

    /** @param array<string, mixed> $row */
    private function hydrate(string $orderNo, array $row): ShippingAddressEntity
    {
        return new ShippingAddressEntity(
            orderNo: $orderNo,
            name01: (string) $row['name01'],
            name02: (string) $row['name02'],
            postalCode: $row['postal_code'] === null ? '' : (string) $row['postal_code'],
            pref: $row['pref_id'] === null ? 0 : (int) $row['pref_id'],
            addr01: $row['addr01'] === null ? '' : (string) $row['addr01'],
            addr02: $row['addr02'] === null ? '' : (string) $row['addr02'],
            phoneNumber: $row['phone_number'] === null ? '' : (string) $row['phone_number'],
        );
    }
}
