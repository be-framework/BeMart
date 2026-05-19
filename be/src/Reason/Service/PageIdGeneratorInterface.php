<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque Page identifiers (Wave 9 CMS slice) — same shape as
 * {@see CategoryIdGeneratorInterface}.
 */
interface PageIdGeneratorInterface
{
    public function generate(): string;
}
