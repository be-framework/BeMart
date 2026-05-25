<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque category identifiers — mirrors
 * {@see AddressIdQueryInterface}. A dedicated interface lets tests
 * stub a deterministic id query per-case (e.g. for asserting list
 * orderings) without rebinding the address or customer-id query.
 */
interface CategoryIdQueryInterface
{
    #[DbQuery('category_next_id')]
    public function next(): AllocatedId;
}
