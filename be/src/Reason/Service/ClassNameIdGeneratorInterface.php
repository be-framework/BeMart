<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque ClassName identifiers (Wave 7) — same shape as
 * {@see CategoryIdGeneratorInterface}.
 */
interface ClassNameIdGeneratorInterface
{
    public function generate(): string;
}
