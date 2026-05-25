<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SortNoFormatException;

/**
 * Display order — EC-CUBE sort_no across dtb_category, dtb_class_name,
 * dtb_product etc. Smaller value = earlier position. Bound 0..9999.
 */
final class SortNo
{
    #[Validate]
    public function validate(int|null $sortNo): void
    {
        if ($sortNo === null) {
            return;
        }

        if ($sortNo < 0 || $sortNo > 9999) {
            throw new SortNoFormatException();
        }
    }
}
