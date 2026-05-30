<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Param\OrderItemList;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Persists the order-time line-item snapshot (dtb_order_item rows) for a
 * finalized order.
 *
 * EC-CUBE freezes each purchased line into dtb_order_item at checkout so
 * later catalog edits never rewrite a past receipt. Both the storefront
 * checkout ({@see \MyVendor\BeMart\Be\Final\CheckoutCompleted}) and the
 * back-office manual order entry
 * ({@see \MyVendor\BeMart\Be\Final\AdminOrderCreated}) converge on this
 * single write surface.
 *
 * `register` resolves the parent `dtb_order.id` from `$orderNo` (the
 * order row is always written first), then fans the item vector out via
 * `JSON_TABLE`. The companion read is
 * {@see OrderItemQueryInterface::listByOrderNo}. The Fake adapter is a
 * no-op (the order-item snapshot is durable-only; the Fake suite asserts
 * the wiring through a stubbed command).
 */
interface OrderItemCommandInterface
{
    /** @param OrderItemList $items the order's full line-item snapshot (may be empty) */
    #[DbQuery('order_item_register')]
    public function register(string $orderNo, OrderItemList $items): void;
}
