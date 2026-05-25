<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SaleTypeNameFormatException;

use function trim;

/**
 * Sale type display name — EC-CUBE 4.3 mtb_sale_type.name.
 *
 * Non-empty display label (e.g. "通常販売", "予約販売").
 */
final class SaleTypeName
{
    #[Validate]
    public function validate(string $saleTypeName): void
    {
        if (trim($saleTypeName) === '') {
            throw new SaleTypeNameFormatException();
        }
    }
}
