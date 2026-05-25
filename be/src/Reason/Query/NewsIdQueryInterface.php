<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque News identifiers (Wave 9).
 */
interface NewsIdQueryInterface
{
    #[DbQuery('news_next_id')]
    public function next(): AllocatedId;
}
