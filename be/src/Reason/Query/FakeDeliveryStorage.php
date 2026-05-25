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
        unset($this->byId[$deliveryId]);
    }
}
