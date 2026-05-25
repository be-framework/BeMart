<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ProductStatusFormatException;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;

use function in_array;

/**
 * Product status (商品ステータス) — the integer column EC-CUBE writes
 * into dtb_product.product_status_id. Wave 8 (doCreateProduct /
 * doUpdateProduct / doBulkUpdateProductStatus) introduces this
 * Semantic so an admin's status assignment is bounded to the
 * recognised set before it reaches the storage layer.
 *
 * Allowed values mirror the ALPS `productStatus` descriptor verbatim:
 *   1 = 公開 (Visible)
 *   2 = 非公開 (Hidden)
 *   3 = 廃止 (Withdrawn — soft delete)
 *
 * The Semantic enforces format only; whether a particular transition
 * is reachable (e.g. admin attempting to "un-withdraw" via bulk
 * status update) is a Phase 2 workflow concern.
 */
final class ProductStatus
{
    /** @var list<int> */
    public const ALLOWED = [
        ProductEntity::STATUS_VISIBLE,
        ProductEntity::STATUS_HIDDEN,
        ProductEntity::STATUS_WITHDRAWN,
    ];

    #[Validate]
    public function validate(int|null $productStatus): void
    {
        if ($productStatus === null) {
            return;
        }

        if (! in_array($productStatus, self::ALLOWED, true)) {
            throw new ProductStatusFormatException();
        }
    }
}
