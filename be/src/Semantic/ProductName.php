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
 * Wave 8 extension: accepts null so partial-update flows
 * (doUpdateProduct) can pass `productName=null` to mean "do not change
 * this field". Same convention as {@see \MyVendor\BeMart\Be\Semantic\Charge}
 * et al.
 *
 * @link https://schema.org/name
 */
final class ProductName
{
    #[Validate]
    public function validate(string|null $productName): void
    {
        if ($productName === null) {
            return;
        }

        if (trim($productName) === '') {
            throw new EmptyProductNameException();
        }

        if (mb_strlen($productName) > 255) {
            throw new ProductNameTooLongException();
        }
    }
}
