<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

use function in_array;

/** SelectorType — the namespace used to interpret Selector. */
final class SelectorType
{
    #[Validate]
    public function validate(string $selectorType): void
    {
        if (! in_array($selectorType, ['email', 'customerId'], true)) {
            throw new \InvalidArgumentException('selectorType must be email or customerId.');
        }
    }
}
