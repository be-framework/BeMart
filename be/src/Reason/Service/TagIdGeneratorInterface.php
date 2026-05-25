<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque Tag identifiers (Wave 9).
 */
interface TagIdGeneratorInterface
{
    public function generate(): string;
}
