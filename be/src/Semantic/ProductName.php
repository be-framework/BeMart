<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\EmptyProductNameException;
use MyVendor\BeMart\Be\Exception\ProductNameTooLongException;

use function mb_strlen;
use function trim;

/**
 * Product display name — EC-CUBE 4.3 dtb_product.name.
 *
 * Non-empty, <= 255 chars.
 *
 * @link https://schema.org/name
 */
final class ProductName
{
    #[Validate]
    public function validate(string $productName): void
    {
        if (trim($productName) === '') {
            throw new EmptyProductNameException();
        }

        if (mb_strlen($productName) > 255) {
            throw new ProductNameTooLongException();
        }
    }
}
