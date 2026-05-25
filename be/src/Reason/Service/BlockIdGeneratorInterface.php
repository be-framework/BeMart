<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque Block identifiers (Wave 9).
 */
interface BlockIdGeneratorInterface
{
    public function generate(): string;
}
