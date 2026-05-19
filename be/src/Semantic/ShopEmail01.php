<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\EmailFormatException;

use function mb_strlen;
use function str_contains;

/**
 * Shop email address (send-from / BCC) — EC-CUBE 4.3
 * dtb_base_info.shop_email_01. Optional, but when present must satisfy
 * the same basic shape as customer Email (contains `@`, 3..254 chars)
 * because it is what most mail templates use as the From / BCC address.
 *
 * Reuses EmailFormatException (same shape).
 */
final class ShopEmail01
{
    #[Validate]
    public function validate(string|null $shopEmail01): void
    {
        if ($shopEmail01 === null || $shopEmail01 === '') {
            return;
        }

        if (! str_contains($shopEmail01, '@') || mb_strlen($shopEmail01) > 254 || mb_strlen($shopEmail01) < 3) {
            throw new EmailFormatException();
        }
    }
}
