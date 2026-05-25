<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

interface CustomerIdQueryInterface
{
    #[DbQuery('customer_next_id')]
    public function next(): AllocatedId;
}
