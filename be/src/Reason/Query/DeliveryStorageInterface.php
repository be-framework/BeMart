<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryFeeEntity;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryTimeEntity;
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
    #[DbQuery('tdelivery_list')]
    public function list(): array;

    #[DbQuery('tdelivery_get')]
    public function item(string $deliveryId): DeliveryEntity|null;

    #[DbQuery('tdelivery_put')]
    public function put(DeliveryEntity $delivery): void;

    #[DbQuery('tdelivery_delete')]
    public function delete(string $deliveryId): void;

    #[DbQuery('tdelivery_reorder')]
    public function reorder(string $deliveryId, int $sortNo): void;

    #[DbQuery('tdelivery_visible')]
    public function setVisible(string $deliveryId, bool $visible): void;

    /**
     * Visible お届け時間 slots for a delivery method, sorted by sort_no.
     * Drives the お届け時間 <select> options on the checkout page.
     *
     * @return list<DeliveryTimeEntity>
     */
    #[DbQuery('tdelivery_times')]
    public function listTimes(string $deliveryId): array;

    /**
     * Representative base 送料 for a delivery method — the minimum fee
     * across prefectures in dtb_delivery_fee. A method with no fee rows
     * yields fee 0.
     */
    #[DbQuery('tdelivery_base_fee')]
    public function baseFee(string $deliveryId): DeliveryFeeEntity|null;
}
