<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ShopNameFormatException;

use function mb_strlen;

/**
 * Shop name kana — EC-CUBE 4.3 dtb_base_info.shop_kana. Optional.
 * Length-bounded only; the Japanese-kana character set is NOT enforced
 * because admin operators occasionally store Latin-letter readings of
 * foreign brand names here (matching EC-CUBE's own laxity).
 *
 * Reuses ShopNameFormatException — same shape (length-bound failure on
 * a shop-info display name).
 */
final class ShopKana
{
    #[Validate]
    public function validate(string|null $shopKana): void
    {
        if ($shopKana === null || $shopKana === '') {
            return;
        }

        if (mb_strlen($shopKana) > 255) {
            throw new ShopNameFormatException();
        }
    }
}
