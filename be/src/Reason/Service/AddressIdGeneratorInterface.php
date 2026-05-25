<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque address identifiers — mirrors
 * CustomerIdGeneratorInterface (Pilot 4). A dedicated interface lets
 * tests stub a deterministic generator per-case (e.g. for asserting
 * Location headers) without rebinding the customer-id generator.
 */
interface AddressIdGeneratorInterface
{
    public function generate(): string;
}
