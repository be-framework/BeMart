<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SaleLimitFormatException;

/**
 * Per-customer purchase ceiling — EC-CUBE 4.3
 * dtb_product_class.sale_limit.
 *
 * Nullable: null = no limit. Otherwise a positive integer (1 or more).
 */
final class SaleLimit
{
    #[Validate]
    public function validate(int|null $saleLimit): void
    {
        if ($saleLimit === null) {
            return;
        }

        if ($saleLimit < 1) {
            throw new SaleLimitFormatException();
        }
    }
}
