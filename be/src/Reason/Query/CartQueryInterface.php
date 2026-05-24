<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Query\Factory\CartFactory;
use Ray\MediaQuery\Annotation\DbQuery;

interface CartQueryInterface
{
    #[DbQuery('cart_by_key', factory: CartFactory::class)]
    public function item(string $cartKey): CartEntity|null;

    /**
     * All carts for a given session prefix — one per saleType.
     * Returns an empty list when the shopping session has not added
     * anything yet. Pilot 9 (goCart).
     *
     * @return list<CartEntity>
     */
    #[DbQuery('cart_by_session_prefix', factory: CartFactory::class)]
    public function listBySessionPrefix(string $sessionPrefix): array;
}
