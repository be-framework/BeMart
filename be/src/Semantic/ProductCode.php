<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ProductCodeFormatException;

use function mb_strlen;
use function preg_match;
use function trim;

/**
 * Product code — EC-CUBE 4.3 dtb_product_class.code.
 *
 * Non-empty, <= 50 chars, [A-Za-z0-9._-] only.
 *
 * @link https://schema.org/sku
 */
final class ProductCode
{
    #[Validate]
    public function validate(string $productCode): void
    {
        if (trim($productCode) === '' || mb_strlen($productCode) > 50) {
            throw new ProductCodeFormatException();
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $productCode)) {
            throw new ProductCodeFormatException();
        }
    }
}
