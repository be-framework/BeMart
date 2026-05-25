<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use MyVendor\BeMart\Be\Reason\Query\Factory\ProductClassFactory;
use Ray\MediaQuery\Annotation\DbQuery;

interface ProductClassQueryInterface
{
    #[DbQuery('product_class_get', factory: ProductClassFactory::class)]
    public function item(string $productCode): ProductClassEntity|null;
}
