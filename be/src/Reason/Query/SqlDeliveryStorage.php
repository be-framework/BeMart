<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use Override;

use function ctype_digit;

final class SqlDeliveryStorage implements DeliveryStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<DeliveryEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->tdelivery_list());
    }

    #[Override]
    public function getById(string $deliveryId): DeliveryEntity|null
    {
        if (! ctype_digit($deliveryId)) {
            return null;
        }
        $row = $this->db->tdelivery_get(id: (int) $deliveryId);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(DeliveryEntity $delivery): void
    {
        if (! ctype_digit($delivery->deliveryId)) {
            return;
        }
        $id = (int) $delivery->deliveryId;
        if ($this->db->tdelivery_exists(id: $id) === null) {
            $this->db->tdelivery_insert(id: $id, name: $delivery->deliveryName, visible: (int) $delivery->visible);

            return;
        }

        $this->db->tdelivery_update(id: $id, name: $delivery->deliveryName, visible: (int) $delivery->visible);
    }

    #[Override]
    public function remove(string $deliveryId): void
    {
        if (ctype_digit($deliveryId)) {
            $this->db->tdelivery_delete(id: (int) $deliveryId);
        }
    }

    #[Override]
    public function reorder(string $deliveryId, int $sortNo): void
    {
        if (ctype_digit($deliveryId)) {
            $this->db->tdelivery_reorder(id: (int) $deliveryId, sortNo: $sortNo);
        }
    }

    #[Override]
    public function setVisible(string $deliveryId, bool $visible): void
    {
        if (ctype_digit($deliveryId)) {
            $this->db->tdelivery_visible(id: (int) $deliveryId, visible: (int) $visible);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): DeliveryEntity
    {
        return new DeliveryEntity((string) (int) $row['id'], (string) ($row['name'] ?? ''), (bool) $row['visible']);
    }
}
