<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SortNoFormatException;

/**
 * Display order — EC-CUBE dtb_category.sort_no, dtb_class_name.sort_no
 * and friends. Smaller value = earlier position in the admin UI. We
 * bound it to 0..9999 so a tampered form cannot stuff in arbitrary
 * integers; values outside the range raise.
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
