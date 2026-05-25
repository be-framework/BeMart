<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque Page identifiers (Wave 9 CMS slice) — same shape as
 * {@see CategoryIdQueryInterface}.
 */
interface PageIdQueryInterface
{
    #[DbQuery('page_next_id')]
    public function next(): AllocatedId;
}
