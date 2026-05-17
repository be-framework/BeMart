<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;

interface ProductClassQueryInterface
{
    public function item(string $productCode): ProductClassEntity|null;
}
