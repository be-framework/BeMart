<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque Block identifiers (Wave 9).
 */
interface BlockIdQueryInterface
{
    #[DbQuery('block_next_id')]
    public function next(): AllocatedId;
}
