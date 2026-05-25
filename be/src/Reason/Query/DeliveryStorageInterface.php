<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;

/**
 * Admin delivery-method master — unified Query + Command (Wave 9θ).
 *
 * Same convention as {@see ClassNameStorageInterface}.
 *
 *   - reorder(deliveryId, sortNo): generic `doSortNoMove` — rewrites
 *     the storage-only `sort_no` column of dtb_delivery.
 *   - setVisible(deliveryId, visible): generic `doToggleVisible` —
 *     rewrites the `visible` column. Unlike sort_no, `visible` IS
 *     projected onto {@see DeliveryEntity}, so the Fake also rebuilds
 *     the cached entity to keep its `list()` projection consistent.
 */
interface DeliveryStorageInterface
{
    /** @return list<DeliveryEntity> */
    public function list(): array;

    public function getById(string $deliveryId): DeliveryEntity|null;

    public function put(DeliveryEntity $delivery): void;

    public function remove(string $deliveryId): void;

    public function reorder(string $deliveryId, int $sortNo): void;

    public function setVisible(string $deliveryId, bool $visible): void;
}
