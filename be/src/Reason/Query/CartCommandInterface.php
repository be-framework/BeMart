<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Query\Result\SavedCart;
use Ray\MediaQuery\Annotation\DbQuery;

interface CartCommandInterface
{
    /**
     * Persist (or overwrite) a Cart aggregate keyed by cartKey.
     *
     * Phase 1 stores into an in-memory map; Phase 2 swaps to an
     * a cart upsert against dtb_cart + dtb_cart_item.
     */
    #[DbQuery('cart_save')]
    public function save(CartEntity $cart): SavedCart;

    /**
     * Remove the Cart whose preOrderId matches the finalized order.
     *
     * Called from CheckoutCompleted after the order has been persisted
     * and the confirmation mail has been queued. Maps to EC-CUBE's
     * CartService::clear() that runs at the tail of PurchaseFlow
     * (shopping flow). A missing cart is a no-op — the checkout already
     * succeeded and a stale fixture should not break the Final.
     */
    #[DbQuery('cart_clear_pre_order')]
    public function clearByPreOrderId(string $preOrderId): void;

    /**
     * Remove every Cart whose cartKey begins with the supplied
     * sessionPrefix. The cartKey shape is `{sessionPrefix}_{saleTypeId}`,
     * so the prefix scopes a single shopping session into N carts
     * (one per sale type). Pilot doWithdrawCustomer uses this to wipe
     * the leaving customer's entire cart footprint in one call. A
     * sessionPrefix with no matching carts is a no-op.
     */
    #[DbQuery('cart_clear_session_prefix')]
    public function clearBySessionPrefix(string $sessionPrefix): void;
}
