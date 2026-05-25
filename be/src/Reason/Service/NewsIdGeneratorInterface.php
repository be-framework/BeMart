<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque News identifiers (Wave 9).
 */
interface NewsIdGeneratorInterface
{
    public function generate(): string;
}
