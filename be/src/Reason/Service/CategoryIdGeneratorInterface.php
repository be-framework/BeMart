<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque category identifiers — mirrors
 * {@see AddressIdGeneratorInterface}. A dedicated interface lets tests
 * stub a deterministic generator per-case (e.g. for asserting list
 * orderings) without rebinding the address or customer-id generator.
 */
interface CategoryIdGeneratorInterface
{
    public function generate(): string;
}
