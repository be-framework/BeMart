<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ShopNameFormatException;

use function mb_strlen;

/**
 * Shop name (English) — EC-CUBE 4.3 dtb_base_info.shop_name_eng.
 * Optional. Length-bounded only; the character set is intentionally
 * not restricted (admins frequently put mixed-script tag-lines here).
 */
final class ShopNameEng
{
    #[Validate]
    public function validate(string|null $shopNameEng): void
    {
        if ($shopNameEng === null || $shopNameEng === '') {
            return;
        }

        if (mb_strlen($shopNameEng) > 255) {
            throw new ShopNameFormatException();
        }
    }
}
