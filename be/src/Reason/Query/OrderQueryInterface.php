<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;

/**
 * Reads Order aggregates used by Order- and reorder-related flows.
 *
 * `byPreOrderId` returns a pre-order (orderStatus=PROCESSING(8)) — null when
 * no pre-order exists for the given id (e.g. session expired or the customer
 * skipped the Shopping page).
 *
 * `byOrderNo` returns the finalized-order header (orderStatus=NEW(1) onwards)
 * — the row that EC-CUBE persists into dtb_order at checkout time. Returns
 * null when the orderNo is unknown. Pilot 12 (doReorder) uses this to AUTHZ
 * the request against the order's customerId before replaying the items.
 *
 * `itemsByOrderNo` returns the line-item snapshot of a finalized Order
 * (orderStatus=NEW(1) onwards) — the rows that EC-CUBE persists into
 * dtb_order_item at checkout time. Returns an empty list when the order has
 * no items recorded (unknown orderNo, or a fixture without items wired).
 * Pilot 12 (doReorder) is the first consumer.
 *
 * `listByCustomer` returns the customer's finalized orders sorted by
 * `orderDate` descending (newest first), capped by `$limit` and offset by
 * `$offset` rows. The Mypage dashboard (goMypage) uses the head of the
 * list to render the "最近のご注文" summary panel; goOrderHistory uses
 * the `$offset` argument to walk past the first page when rendering the
 * customer's full order history. Returns an empty list when the customer
 * has no past orders (or `$offset` walks past the end).
 *
 * `listAll` returns the global finalized-order list sorted by `orderDate`
 * descending (newest first), advanced by `$offset` and capped at `$limit`.
 * Wave 7 (goOrderList) is the first consumer — the admin grid pulls the
 * head of the list for the back-office screen. Unlike `listByCustomer`
 * this is unfiltered by ownership: every finalized order on the system is
 * in scope. AUTHZ is enforced by the Final via {@see AdminSessionInterface}
 * — there is no API for a non-admin to call this directly.
 */
interface OrderQueryInterface
{
    public function byPreOrderId(string $preOrderId): ?OrderEntity;

    public function byOrderNo(string $orderNo): ?FinalizedOrderEntity;

    /** @return list<OrderItemEntity> */
    public function itemsByOrderNo(string $orderNo): array;

    /** @return list<FinalizedOrderEntity> */
    public function listByCustomer(string $customerId, int $limit = 10, int $offset = 0): array;

    /** @return list<FinalizedOrderEntity> */
    public function listAll(int $limit = 50, int $offset = 0): array;
}
