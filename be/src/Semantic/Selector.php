<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SelectorFormatException;

use function mb_strlen;

/**
 * Admin customer selector — the opaque lookup value used by goAdminCustomer.
 *
 * The accompanying `selectorType` tells whether this value is interpreted as
 * `customerId` or `email`. This Semantic therefore enforces only the transport
 * boundary common to both meanings: a non-empty, bounded scalar selector. The
 * Resource layer keeps email syntax validation close to the HTTP parameter
 * fallback, and CustomerQuery remains responsible for existence.
 */
final class Selector
{
    #[Validate]
    public function validate(string $selector): void
    {
        if ($selector === '' || mb_strlen($selector) > 255) {
            throw new SelectorFormatException();
        }
    }
}
