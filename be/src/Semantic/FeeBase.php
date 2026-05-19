<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Base delivery fee in JPY (Wave 9θ). Non-negative.
 */
final class FeeBase
{
    #[Validate]
    public function validate(int|null $feeBase): void
    {
        // Type assertion only.
    }
}
