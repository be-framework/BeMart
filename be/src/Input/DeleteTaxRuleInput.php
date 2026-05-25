<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TaxRuleDeleted;

/**
 * Input for doDeleteTaxRule — admin removes a tax-rule master row
 * (Wave 9θ).
 *
 *   DeleteTaxRuleInput → TaxRuleDeleted (Direct, idempotent)
 */
#[Be(TaxRuleDeleted::class)]
final readonly class DeleteTaxRuleInput
{
    /**
     * @psalm-taint-source input $taxRuleId
     */
    public function __construct(
        public string $taxRuleId,
    ) {
    }
}
