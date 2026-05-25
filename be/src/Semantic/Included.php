<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Included — the list of past-order items that survived the Pilot 12
 * (doReorder) current-catalog re-projection. Each row is an associative
 * array carrying the productCode + current ProductClass-derived values
 * + the capped quantity (see {@see \MyVendor\BeMart\Be\Being\ReorderResolving}
 * for the exact shape). Type assertion only — the structure is enforced
 * by the producing Being's psalm-typed `@var` annotation.
 */
final class Included
{
    #[Validate]
    public function validate(array $included): void
    {
        // Type assertion only — list shape is contracted upstream.
    }
}
