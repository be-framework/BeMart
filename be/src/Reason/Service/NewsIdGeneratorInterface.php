<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\GeneratedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates opaque News identifiers (Wave 9).
 */
interface NewsIdGeneratorInterface
{
    #[DbQuery('news_next_id')]
    public function generate(): GeneratedId;
}
