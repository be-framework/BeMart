<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Front-facing visibility flag (Wave 9θ).
 *
 * Shared across Payment / Delivery / etc. master tables: true = the
 * row is surfaced to customers, false = it is hidden but its
 * historical references stay intact.
 */
final class Visible
{
    #[Validate]
    public function validate(bool|null $visible): void
    {
        // Type assertion only.
    }
}
