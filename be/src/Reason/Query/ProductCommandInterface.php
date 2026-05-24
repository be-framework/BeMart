<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\Result\CopiedProduct;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Write-side Product command — Wave 8 (admin product management).
 *
 * Split from ProductQueryInterface to keep CQRS boundaries explicit
 * (mirrors CustomerCommandInterface). All methods expect entity
 * arguments that have already been merged with the persisted state
 * by the Final — this interface does NOT perform partial-merge.
 *
 * Deletion is logical: `delete($productCode)` flips
 * `ProductEntity::productStatus` to `STATUS_WITHDRAWN` (=3) per the
 * ALPS doc ("ステータス変更による論理削除"). Order-history snapshots
 * reference frozen product copy data and are unaffected.
 */
interface ProductCommandInterface
{
    /**
     * Persist a brand-new product. Caller MUST have verified the
     * productCode is not already in use (the product existence check
     * or equivalent). Replays with the same code overwrite — the
     * Final is responsible for the 409 guard.
     */
    #[DbQuery('product_create')]
    public function create(ProductEntity $product): void;

    /**
     * Replace the persisted product with the supplied entity. Caller
     * MUST construct the entity from the persisted current state
     * merged with the validated update fields; this interface does
     * not perform the merge itself.
     */
    #[DbQuery('product_update')]
    public function update(ProductEntity $product): void;

    /**
     * Soft-delete: flip productStatus to STATUS_WITHDRAWN (=3).
     * Idempotent — a second call against an already-withdrawn product
     * is a no-op. Silently does nothing when productCode is not in
     * the store.
     */
    #[DbQuery('product_soft_delete')]
    public function delete(string $productCode): void;

    /**
     * Clone a product under a new productCode. The cloned row carries
     * the source's price / stock / description / etc. with the
     * productName prefixed by "(コピー) " per the ALPS doc, and is
     * created in STATUS_VISIBLE regardless of the source's status
     * (admin convention: the copy is a fresh draft). Returns the
     * newly-persisted entity.
     */
    #[DbQuery('product_copy', factory: \MyVendor\BeMart\Be\Reason\Query\Factory\ProductFactory::class)]
    public function copy(string $sourceCode, string $newCode): CopiedProduct;
}
