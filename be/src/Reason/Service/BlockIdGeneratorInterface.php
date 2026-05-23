<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque Block identifiers (Wave 9).
 */
interface BlockIdGeneratorInterface
{
    #[DbQuery('block_next_id')]
    public function generate(): AllocatedId;
}
