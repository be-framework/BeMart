<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;

interface CartCommandInterface
{
    /**
     * Persist (or overwrite) a Cart aggregate keyed by cartKey.
     *
     * Phase 1 stores into an in-memory map; Phase 2 swaps to an
     * INSERT … ON DUPLICATE KEY UPDATE against dtb_cart + dtb_cart_item.
     */
    public function save(CartEntity $cart): void;

    /**
     * Remove the Cart whose preOrderId matches the finalized order.
     *
     * Called from CheckoutCompleted after the order has been persisted
     * and the confirmation mail has been queued. Maps to EC-CUBE's
     * CartService::clear() that runs at the tail of PurchaseFlow
     * (shopping flow). A missing cart is a no-op — the checkout already
     * succeeded and a stale fixture should not break the Final.
     */
    public function clearByPreOrderId(string $preOrderId): void;
}
