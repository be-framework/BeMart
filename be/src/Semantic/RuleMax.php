<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Upper order-total bound for payment-method eligibility (Wave 9θ).
 * Null = no upper bound.
 */
final class RuleMax
{
    #[Validate]
    public function validate(int|null $ruleMax): void
    {
        // Type assertion only.
    }
}
