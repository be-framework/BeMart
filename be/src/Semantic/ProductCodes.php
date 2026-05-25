<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ProductCodesFormatException;

use function count;
use function is_string;
use function mb_strlen;
use function preg_match;
use function trim;

/**
 * List of product codes — Wave 8 (doBulkUpdateProductStatus).
 *
 * Plural counterpart of {@see ProductCode} used by the bulk-update
 * transition. Each element must satisfy the singular ProductCode
 * format rules; the list itself must be non-empty and capped at 100
 * elements (an admin operator should not be flipping more than a
 * page's worth of products in one request).
 *
 * @link https://schema.org/sku
 */
final class ProductCodes
{
    /** @param array<int, mixed> $productCodes */
    #[Validate]
    public function validate(array $productCodes): void
    {
        $count = count($productCodes);
        if ($count < 1 || $count > 100) {
            throw new ProductCodesFormatException();
        }

        foreach ($productCodes as $code) {
            if (! is_string($code)) {
                throw new ProductCodesFormatException();
            }

            if (trim($code) === '' || mb_strlen($code) > 50) {
                throw new ProductCodesFormatException();
            }

            if (! preg_match('/^[A-Za-z0-9._-]+$/', $code)) {
                throw new ProductCodesFormatException();
            }
        }
    }
}
