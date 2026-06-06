<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Customer selector type used by admin customer edit/search flows.
 *
 * Optional free-form selector metadata. Registering this semantic removes
 * ontology fallback notices while keeping validation authority in Be.
 */
final class SelectorType
{
    #[Validate]
    public function validate(string|null $selectorType): void
    {
    }
}
