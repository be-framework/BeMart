<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

interface CustomerIdGeneratorInterface
{
    #[DbQuery('customer_next_id')]
    public function generate(): AllocatedId;
}
