<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminProductsStatusBulkUpdated;

/**
 * Input for doBulkUpdateProductStatus — admin flips productStatus
 * across several products in one call.
 *
 *   AdminBulkUpdateProductStatusInput → AdminProductsStatusBulkUpdated
 *                                       (Direct, unsafe)
 *
 * ALPS `doBulkUpdateProductStatus.type=unsafe`. Both fields are
 * format-validated by their matching Semantic classes
 * (Semantic\ProductCodes + Semantic\ProductStatus). Unknown codes are
 * silently skipped at the Final / Command layer — the admin UI
 * compares `requestedCount` and `changedCount` to surface anomalies.
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(AdminProductsStatusBulkUpdated::class)]
final readonly class AdminBulkUpdateProductStatusInput
{
    /**
     * @param list<string> $productCodes
     *
     * @psalm-taint-source input $productCodes
     * @psalm-taint-source input $productStatus
     */
    public function __construct(
        public array $productCodes,
        public int $productStatus,
    ) {
    }
}
