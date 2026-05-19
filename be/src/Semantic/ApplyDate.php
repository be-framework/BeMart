<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Tax rule application date (Wave 9θ). ISO-8601 timestamp after which
 * a tax rule starts to apply.
 */
final class ApplyDate
{
    #[Validate]
    public function validate(string|null $applyDate): void
    {
        // Type assertion only.
    }
}
