<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Selector type discriminator for admin lookup inputs.
 *
 * Current resources pass either "email" or "customerId"; resource-level
 * branching owns the concrete lookup semantics.
 */
final class SelectorType
{
    #[Validate]
    public function validate(string $selectorType): void
    {
        // Type assertion only.
    }
}
