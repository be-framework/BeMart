<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ShopNameFormatException;

use function mb_strlen;

/**
 * Shop welcome message — EC-CUBE 4.3 dtb_base_info.shop_message.
 * Optional. Free-form. Length-capped at 1024 chars (EC-CUBE itself
 * uses TEXT but unbounded admin-edited HTML / Markdown is an abuse
 * surface — same defensive bound as ContactContents).
 */
final class ShopMessage
{
    #[Validate]
    public function validate(string|null $shopMessage): void
    {
        if ($shopMessage === null || $shopMessage === '') {
            return;
        }

        if (mb_strlen($shopMessage) > 1024) {
            throw new ShopNameFormatException();
        }
    }
}
