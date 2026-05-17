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
}
