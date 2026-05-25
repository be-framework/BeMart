<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * One row in dtb_order_item, the order-time snapshot of a purchased line.
 *
 * Mirrors CartItemEntity's shape but scoped to a finalized Order — adds
 * `orderNo` (foreign key into dtb_order) and `productName` because the
 * order-item row freezes the display name at purchase time (catalog edits
 * after checkout must not retroactively rewrite past receipts).
 *
 * `unitPrice` is the per-unit price02 captured at order-finalization time;
 * extending price * quantity reproduces the line subtotal as printed on the
 * original confirmation mail.
 */
final readonly class OrderItemEntity
{
    public function __construct(
        public string $orderNo,
        public string $productCode,
        public string $productName,
        public int $quantity,
        public int $unitPrice,
    ) {
    }
}
