<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SelectorTypeFormatException;

/**
 * Admin customer selector type — discriminator for goAdminCustomer lookup.
 *
 * `customerId` selects the canonical customer identifier. `email` preserves the
 * legacy admin lookup path used by existing forms and tests. No other selector
 * namespace is part of this boundary contract.
 */
final class SelectorType
{
    #[Validate]
    public function validate(string $selectorType): void
    {
        if ($selectorType !== 'customerId' && $selectorType !== 'email') {
            throw new SelectorTypeFormatException();
        }
    }
}
