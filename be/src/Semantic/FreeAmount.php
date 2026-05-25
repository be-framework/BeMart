<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Order-total threshold above which delivery becomes free (Wave 9θ).
 * Null = never free.
 */
final class FreeAmount
{
    #[Validate]
    public function validate(int|null $freeAmount): void
    {
        // Type assertion only.
    }
}
