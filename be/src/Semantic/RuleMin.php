<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Lower order-total bound for payment-method eligibility (Wave 9θ).
 * Null = no lower bound.
 */
final class RuleMin
{
    #[Validate]
    public function validate(int|null $ruleMin): void
    {
        // Type assertion only.
    }
}
