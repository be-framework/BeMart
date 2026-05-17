<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;

interface ProductQueryInterface
{
    public function item(string $productCode): ProductEntity|null;
}
