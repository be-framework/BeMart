<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;

/**
 * Reads Order aggregates used by Order- and reorder-related flows.
 *
 * `byPreOrderId` returns a pre-order (orderStatus=PROCESSING(8)) — null when
 * no pre-order exists for the given id (e.g. session expired or the customer
 * skipped the Shopping page).
 *
 * `itemsByOrderNo` returns the line-item snapshot of a finalized Order
 * (orderStatus=NEW(1) onwards) — the rows that EC-CUBE persists into
 * dtb_order_item at checkout time. Returns an empty list when the order has
 * no items recorded (unknown orderNo, or a fixture without items wired).
 * Pilot 12 (doReorder) is the first consumer.
 */
interface OrderQueryInterface
{
    public function byPreOrderId(string $preOrderId): ?OrderEntity;

    /** @return list<OrderItemEntity> */
    public function itemsByOrderNo(string $orderNo): array;
}
