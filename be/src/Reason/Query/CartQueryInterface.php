<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;

interface CartQueryInterface
{
    public function byCartKey(string $cartKey): CartEntity|null;

    /**
     * All carts for a given session prefix — one per saleType.
     * Returns an empty list when the shopping session has not added
     * anything yet. Pilot 9 (goCart).
     *
     * @return list<CartEntity>
     */
    public function bySessionPrefix(string $sessionPrefix): array;
}
