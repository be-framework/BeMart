<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque ClassName identifiers (Wave 7) — same shape as
 * {@see CategoryIdGeneratorInterface}.
 */
interface ClassNameIdGeneratorInterface
{
    #[DbQuery('className_next_id')]
    public function next(): AllocatedId;
}
