<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use Override;

use function array_values;
use function ksort;

/**
 * In-memory Delivery store. Starts empty — tests seed via POST.
 * Singleton so reads see same-request writes.
 */
final class FakeDeliveryStorage implements DeliveryStorageInterface
{
    /** @var array<string, DeliveryEntity> keyed by deliveryId */
    private array $byId = [];

    /**
     * Storage-only `sort_no` per row — dtb_delivery has the column but
     * {@see DeliveryEntity} does not project it.
     *
     * @var array<string, int>
     */
    private array $sortNo = [];

    /** @return list<DeliveryEntity> */
    #[Override]
    public function list(): array
    {
        $rows = $this->byId;
        ksort($rows);

        return array_values($rows);
    }

    #[Override]
    public function getById(string $deliveryId): DeliveryEntity|null
    {
        return $this->byId[$deliveryId] ?? null;
    }

    #[Override]
    public function put(DeliveryEntity $delivery): void
    {
        $this->byId[$delivery->deliveryId] = $delivery;
    }

    #[Override]
    public function remove(string $deliveryId): void
    {
        unset($this->byId[$deliveryId], $this->sortNo[$deliveryId]);
    }

    #[Override]
    public function reorder(string $deliveryId, int $sortNo): void
    {
        if (! isset($this->byId[$deliveryId])) {
            return;
        }

        $this->sortNo[$deliveryId] = $sortNo;
    }

    #[Override]
    public function setVisible(string $deliveryId, bool $visible): void
    {
        $current = $this->byId[$deliveryId] ?? null;
        if ($current === null) {
            return;
        }

        // `visible` IS projected onto DeliveryEntity — rebuild the row
        // so `list()` / `getById()` reflect the toggle.
        $this->byId[$deliveryId] = new DeliveryEntity(
            deliveryId: $current->deliveryId,
            deliveryName: $current->deliveryName,
            visible: $visible,
        );
    }

    /** Test introspection: the `sort_no` last written for a row. */
    public function sortNoOf(string $deliveryId): int|null
    {
        return $this->sortNo[$deliveryId] ?? null;
    }
}
