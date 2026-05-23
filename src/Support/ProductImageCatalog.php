<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support;

use function abs;
use function count;
use function crc32;

/**
 * Small presentation-side image catalog for fake/demo products.
 *
 * EC-CUBE stores product images in `dtb_product_image`; BeMart's current
 * fake product fixture intentionally carries only the product/class columns
 * needed by the migration slices. Until the product-image vertical slice is
 * implemented, keep demo/storefront thumbnails deterministic and explicit
 * here rather than sprinkling `no_image_product` through templates.
 */
final class ProductImageCatalog
{
    public const FALLBACK = 'assets/img/common/no_image_product.png';

    /** @var array<string, string> */
    private const FIXED = [
        // Keep the long-standing sample product image-less so the EC-CUBE
        // fidelity test can still compare the class-less/image-less case.
        'sample-001' => self::FALLBACK,

        // Browser/API-inserted products should not look empty.
        'api-persist-20260522-001' => 'assets/img/top/img_item01_01.jpg',
        'ui-create-20260522-001' => 'assets/img/top/img_item02_01.jpg',
    ];

    /** @var list<string> */
    private const POOL = [
        'assets/img/top/img_item01_01.jpg',
        'assets/img/top/img_item01_02.jpg',
        'assets/img/top/img_item01_03.jpg',
        'assets/img/top/img_item02_01.jpg',
        'assets/img/top/img_item02_02.jpg',
        'assets/img/top/img_item02_03.jpg',
    ];

    public static function forProductCode(string $productCode): string
    {
        if (isset(self::FIXED[$productCode])) {
            return self::FIXED[$productCode];
        }

        return self::POOL[abs(crc32($productCode)) % count(self::POOL)];
    }
}
