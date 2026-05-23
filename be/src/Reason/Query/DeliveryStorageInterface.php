<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use Ray\MediaQuery\Annotation\DbQuery;

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
    #[DbQuery('tdelivery_list', factory: DeliveryEntity::class)]
    public function list(): array;

    #[DbQuery('tdelivery_get', factory: DeliveryEntity::class)]
    public function getById(string $deliveryId): DeliveryEntity|null;

    #[DbQuery('tdelivery_put')]
    public function put(DeliveryEntity $delivery): void;

    #[DbQuery('tdelivery_delete')]
    public function remove(string $deliveryId): void;

    #[DbQuery('tdelivery_reorder')]
    public function reorder(string $deliveryId, int $sortNo): void;

    #[DbQuery('tdelivery_visible')]
    public function setVisible(string $deliveryId, bool $visible): void;
}
