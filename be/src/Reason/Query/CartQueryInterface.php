<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;

interface CartQueryInterface
{
    public function byCartKey(string $cartKey): CartEntity|null;
}
