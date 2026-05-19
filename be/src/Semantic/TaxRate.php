<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Master tax rate (taxRuleRate) — percentage applied at this rule's
 * apply-date (Wave 9θ).
 */
final class TaxRate
{
    #[Validate]
    public function validate(float|null $taxRate): void
    {
        // Type assertion only.
    }
}
