<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\GeneratedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque Page identifiers (Wave 9 CMS slice) — same shape as
 * {@see CategoryIdGeneratorInterface}.
 */
interface PageIdGeneratorInterface
{
    #[DbQuery('page_next_id')]
    public function generate(): GeneratedId;
}
