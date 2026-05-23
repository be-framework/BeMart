<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\Result\BulkStatusUpdateResult;
use Override;
use RuntimeException;

use function sprintf;

final class SqlProductCommand implements ProductCommandInterface
{
    public function __construct(
        private readonly InternalDbQueryInterface $db,
        private readonly ProductQueryInterface $products,
    ) {}

    #[Override]
    public function create(ProductEntity $product): void
    {
        $this->db->product_create(
            productStatus: $product->productStatus,
            name: $product->productName,
            note: $product->note,
            description: $product->description,
            searchWord: $product->searchWord,
            productCode: $product->productCode,
            price02: $product->price02,
            stock: $product->stock,
            stockUnlimited: $product->stock === null ? 1 : 0,
        );
    }

    #[Override]
    public function update(ProductEntity $product): void
    {
        $productId = $this->findProductId($product->productCode);
        if ($productId === null) {
            return;
        }
        $this->db->product_update_header(
            id: $productId,
            name: $product->productName,
            productStatus: $product->productStatus,
            description: $product->description,
            searchWord: $product->searchWord,
            note: $product->note,
        );
        $this->db->product_update_class(
            id: $productId,
            price02: $product->price02,
            stock: $product->stock,
        );
    }

    #[Override]
    public function delete(string $productCode): void
    {
        $productId = $this->findProductId($productCode);
        if ($productId !== null) {
            $this->db->product_soft_delete(id: $productId, setStatus: ProductEntity::STATUS_WITHDRAWN, whereStatus: ProductEntity::STATUS_WITHDRAWN);
        }
    }

    #[Override]
    public function copy(string $sourceCode, string $newCode): ProductEntity
    {
        $source = $this->products->item($sourceCode);
        if ($source === null) {
            throw new RuntimeException(sprintf('Product not found: %s', $sourceCode));
        }
        $copy = new ProductEntity(
            productCode: $newCode,
            productName: '(コピー) ' . $source->productName,
            price02: $source->price02,
            stock: $source->stock,
            productStatus: ProductEntity::STATUS_VISIBLE,
            description: $source->description,
            searchWord: $source->searchWord,
            note: $source->note,
            imagePath: $source->imagePath,
            categoryNames: $source->categoryNames,
            tagNames: $source->tagNames,
            classNames: $source->classNames,
        );
        $this->create($copy);
        return $copy;
    }

    /** @param list<string> $productCodes */
    #[Override]
    public function bulkUpdateStatus(array $productCodes, int $newStatus): BulkStatusUpdateResult
    {
        $changed = 0;
        foreach ($productCodes as $code) {
            $productId = $this->findProductId($code);
            if ($productId === null) {
                continue;
            }
            $changed += $this->db->product_status_update(id: $productId, setStatus: $newStatus, whereStatus: $newStatus)->count;
        }
        return new BulkStatusUpdateResult($changed);
    }

    private function findProductId(string $productCode): int|null
    {
        $row = $this->db->product_find_id(productCode: $productCode);
        return $row === null ? null : (int) $row['product_id'];
    }

}