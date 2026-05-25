<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque ClassCategory identifiers (Wave 7) — same shape as
 * {@see CategoryIdGeneratorInterface}.
 */
interface ClassCategoryIdGeneratorInterface
{
    public function generate(): string;
}
