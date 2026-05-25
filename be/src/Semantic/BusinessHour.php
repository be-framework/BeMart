<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ShopNameFormatException;

use function mb_strlen;

/**
 * Business hour — EC-CUBE 4.3 dtb_base_info.business_hour. Optional.
 * Free-form ("10:00-19:00", "平日のみ", etc.); length-bounded only.
 *
 * Reuses ShopNameFormatException — same shape (free-form shop-info
 * field length-bound failure).
 */
final class BusinessHour
{
    #[Validate]
    public function validate(string|null $businessHour): void
    {
        if ($businessHour === null || $businessHour === '') {
            return;
        }

        if (mb_strlen($businessHour) > 100) {
            throw new ShopNameFormatException();
        }
    }
}
