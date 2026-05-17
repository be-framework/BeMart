<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;

/**
 * Reads a pre-order Order (orderStatus=PROCESSING(8)) by preOrderId.
 *
 * Returns null when no pre-order exists for the given id (e.g. session expired
 * or the customer skipped the Shopping page).
 */
interface OrderQueryInterface
{
    public function byPreOrderId(string $preOrderId): ?OrderEntity;
}
