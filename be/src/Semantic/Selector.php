<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Generic selector value used by admin lookup inputs.
 *
 * Field-specific format validation is selected by the companion selectorType.
 */
final class Selector
{
    #[Validate]
    public function validate(string $selector): void
    {
        // Type assertion only.
    }
}
