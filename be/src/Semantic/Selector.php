<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

use function mb_strlen;

/**
 * Selector — generic lookup key chosen by a paired selectorType.
 *
 * Admin detail screens can be reached by legacy email lookup or by the
 * EC-CUBE route's opaque customer id. The concrete meaning is validated by
 * SelectorType and the downstream query; this semantic only rejects an empty
 * or unreasonably long lookup key.
 */
final class Selector
{
    #[Validate]
    public function validate(string $selector): void
    {
        if (mb_strlen($selector) < 1 || mb_strlen($selector) > 254) {
            throw new \InvalidArgumentException('selector must be 1..254 characters.');
        }
    }
}
