<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;

/**
 * Admin delivery-method master — unified Query + Command (Wave 9θ).
 *
 * Same convention as {@see ClassNameStorageInterface}.
 */
interface DeliveryStorageInterface
{
    /** @return list<DeliveryEntity> */
    public function list(): array;

    public function getById(string $deliveryId): DeliveryEntity|null;

    public function put(DeliveryEntity $delivery): void;

    public function remove(string $deliveryId): void;
}
