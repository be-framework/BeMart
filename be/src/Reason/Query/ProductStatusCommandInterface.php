<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Param\ProductCodeList;
use MyVendor\BeMart\Be\Reason\Query\Result\ProductStatusUpdate;
use Ray\MediaQuery\Annotation\DbQuery;

interface ProductStatusCommandInterface
{
    #[DbQuery('product_status_bulk_update')]
    public function update(ProductCodeList $productCodes, int $newStatus): ProductStatusUpdate;
}
