<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Skipped — the list of past-order items the Pilot 12 (doReorder)
 * current-catalog re-projection could not replay. Each row carries
 * `{productCode, reason}` where reason is `discontinued` or
 * `out-of-stock`. Type assertion only — the structure is enforced by
 * the producing Being's psalm-typed `@var` annotation.
 */
final class Skipped
{
    #[Validate]
    public function validate(array $skipped): void
    {
        // Type assertion only — list shape is contracted upstream.
    }
}
