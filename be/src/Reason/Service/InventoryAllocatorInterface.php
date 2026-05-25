<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;

/**
 * Reserves on-hand stock for every line item in the pre-order.
 *
 * Maps to EC-CUBE's StockReducePostProcessor: decrements ProductClass.stock
 * row-by-row inside the PurchaseFlow. The Reason throws
 * InsufficientStockException if any single line has fewer units on hand than
 * requested; the decrement is then NOT applied (atomic per call).
 *
 * Phase 2 swaps the fake for a Ray.MediaQuery binding against
 * dtb_product_class with row-locking read for row locking.
 */
interface InventoryAllocatorInterface
{
    public function allocate(OrderEntity $preOrder): void;
}
