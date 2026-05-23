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
        private readonly MediaQueryExecutor $db,
        private readonly ProductQueryInterface $products,
    ) {}

    #[Override]
    public function create(ProductEntity $product): void
    {
        $this->db->exec('product_create', $this->values($product));
    }

    #[Override]
    public function update(ProductEntity $product): void
    {
        $productId = $this->findProductId($product->productCode);
        if ($productId === null) {
            return;
        }
        $values = $this->values($product) + ['id' => $productId];
        $this->db->exec('product_update_header', $values);
        $this->db->exec('product_update_class', $values);
    }

    #[Override]
    public function delete(string $productCode): void
    {
        $productId = $this->findProductId($productCode);
        if ($productId !== null) {
            $this->db->exec('product_soft_delete', ['id' => $productId, 'setStatus' => ProductEntity::STATUS_WITHDRAWN, 'whereStatus' => ProductEntity::STATUS_WITHDRAWN]);
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
            $changed += $this->db->affected('product_status_update', ['id' => $productId, 'setStatus' => $newStatus, 'whereStatus' => $newStatus]);
        }
        return new BulkStatusUpdateResult($changed);
    }

    private function findProductId(string $productCode): int|null
    {
        $row = $this->db->row('product_find_id', ['productCode' => $productCode]);
        return $row === null ? null : (int) $row['product_id'];
    }

    /** @return array<string, mixed> */
    private function values(ProductEntity $product): array
    {
        return [
            'productCode' => $product->productCode,
            'name' => $product->productName,
            'price02' => $product->price02,
            'stock' => $product->stock,
            'stockUnlimited' => $product->stock === null ? 1 : 0,
            'productStatus' => $product->productStatus,
            'description' => $product->description,
            'searchWord' => $product->searchWord,
            'note' => $product->note,
        ];
    }
}
