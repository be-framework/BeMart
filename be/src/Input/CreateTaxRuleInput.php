<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\TaxRuleCreated;

/**
 * Input for doCreateTaxRule — admin adds a new tax-rule master row
 * (Wave 9θ).
 *
 *   CreateTaxRuleInput → TaxRuleCreated (Direct, admin AUTHZ)
 *
 * `taxRuleId` is server-generated. There is no corresponding update
 * transition — edits flow as delete + create so the applyDate
 * progression remains an explicit audit trail.
 */
#[Be(TaxRuleCreated::class)]
final readonly class CreateTaxRuleInput
{
    /**
     * @psalm-taint-source input $taxRate
     * @psalm-taint-source input $applyDate
     * @psalm-taint-source input $roundingType
     */
    public function __construct(
        public float $taxRate,
        public string $applyDate,
        public int $roundingType = 1,
    ) {
    }
}
