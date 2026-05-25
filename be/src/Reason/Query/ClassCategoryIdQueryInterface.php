<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque ClassCategory identifiers (Wave 7) — same shape as
 * {@see CategoryIdQueryInterface}.
 */
interface ClassCategoryIdQueryInterface
{
    #[DbQuery('classCategory_next_id')]
    public function next(): AllocatedId;
}
