<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\InvalidStockException;

/**
 * Stock quantity — EC-CUBE 4.3 dtb_product_class.stock.
 *
 * Nullable: null = stock_unlimited true. 0 — 9,999,999,999 otherwise.
 */
final class Stock
{
    #[Validate]
    public function validate(int|null $stock): void
    {
        if ($stock === null) {
            return;
        }

        if ($stock < 0 || $stock > 9_999_999_999) {
            throw new InvalidStockException();
        }
    }
}
