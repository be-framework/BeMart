<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates opaque TaxRule identifiers (Wave 9θ).
 */
interface TaxRuleIdGeneratorInterface
{
    public function generate(): string;
}
