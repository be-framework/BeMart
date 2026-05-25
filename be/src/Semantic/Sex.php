<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SexFormatException;

/**
 * Sex — mtb_sex code. 1=Male, 2=Female, 3=Other, 4=Prefer not to say.
 */
final class Sex
{
    #[Validate]
    public function validate(int|null $sex): void
    {
        if ($sex === null) {
            return;
        }

        if ($sex < 1 || $sex > 4) {
            throw new SexFormatException();
        }
    }
}
