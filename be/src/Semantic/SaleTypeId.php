<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SaleTypeIdFormatException;

/**
 * Sale type identifier — EC-CUBE 4.3 dtb_product_class.sale_type_id.
 *
 * Observed master-data values:
 *   1 = 通常販売 (normal sale)
 *   2 = 予約販売 (pre-order)
 *   3 = ダウンロード販売 (download sale)
 *
 * Validated as a positive integer. Lower bound is locked at 1 because
 * mtb_sale_type ids are 1-indexed; the upper bound is intentionally
 * left open so new sale types added to master data do not break
 * domain code.
 */
final class SaleTypeId
{
    #[Validate]
    public function validate(int $saleTypeId): void
    {
        if ($saleTypeId < 1) {
            throw new SaleTypeIdFormatException();
        }
    }
}
