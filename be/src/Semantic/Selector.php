<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Customer selector value used by admin customer edit/search flows.
 *
 * EC-CUBE form ports pass this optional selector through Be semantic
 * validation; the value itself is intentionally transport-shaped here,
 * while selector-specific interpretation stays in the application flow.
 */
final class Selector
{
    #[Validate]
    public function validate(string|null $selector): void
    {
    }
}
