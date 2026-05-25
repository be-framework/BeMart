<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * One row in dtb_cart_item, flattened for Pilot 2 scope.
 *
 * `price` is the per-unit price02 captured at add time (snapshot, not
 * a live join). `quantity` is the post-adjustment value (after Stock
 * and SaleLimit caps).
 */
final readonly class CartItemEntity
{
    public function __construct(
        public string $productCode,
        public int $quantity,
        public int $price,
    ) {
    }
}
