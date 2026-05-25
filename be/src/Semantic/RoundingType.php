<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Tax rounding type (Wave 9θ). 1 = 四捨五入, 2 = 切り捨て, 3 = 切り上げ.
 */
final class RoundingType
{
    #[Validate]
    public function validate(int|null $roundingType): void
    {
        // Type assertion only.
    }
}
