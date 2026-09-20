<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque ProductClass identifiers (Wave: product-class-write)
 * — same shape as {@see ClassCategoryIdQueryInterface}.
 */
interface ProductClassRegisterIdQueryInterface
{
    #[DbQuery('productClass_next_id')]
    public function next(): AllocatedId;
}
