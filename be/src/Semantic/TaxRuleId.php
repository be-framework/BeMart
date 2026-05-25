<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Tax-rule master id — server-derived. Provided by
 * TaxRuleIdProvider (Wave 9θ). Type assertion only — the
 * provider is the contract.
 */
final class TaxRuleId
{
    #[Validate]
    public function validate(string|null $taxRuleId): void
    {
        // Type assertion only — provider is the contract.
    }
}
