<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ShopNameFormatException;

use function mb_strlen;
use function trim;

/**
 * Shop display name — EC-CUBE 4.3 dtb_base_info.shop_name.
 *
 * Non-empty, <= 255 chars. The shop name renders in the storefront
 * header, on order confirmation emails, and in invoices; it MUST be
 * present (an empty shop name would break those views).
 *
 * @link https://schema.org/name
 */
final class ShopName
{
    #[Validate]
    public function validate(string $shopName): void
    {
        if (trim($shopName) === '' || mb_strlen($shopName) > 255) {
            throw new ShopNameFormatException();
        }
    }
}
