<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use Override;
use RuntimeException;

use function sprintf;

final class FakeProductCommand implements ProductCommandInterface
{
    public function __construct(
        private readonly FakeProductStorage $storage,
    ) {
    }

    #[Override]
    public function create(ProductEntity $product): void
    {
        $this->storage->put($product);
    }

    #[Override]
    public function update(ProductEntity $product): void
    {
        $this->storage->put($product);
    }

    #[Override]
    public function delete(string $productCode): void
    {
        $current = $this->storage->getByCode($productCode);
        if ($current === null) {
            return;
        }

        if ($current->productStatus === ProductEntity::STATUS_WITHDRAWN) {
            return;
        }

        $this->storage->put(new ProductEntity(
            productCode: $current->productCode,
            productName: $current->productName,
            price02: $current->price02,
            stock: $current->stock,
            productStatus: ProductEntity::STATUS_WITHDRAWN,
            description: $current->description,
            searchWord: $current->searchWord,
            note: $current->note,
        ));
    }

    #[Override]
    public function copy(string $sourceCode, string $newCode): ProductEntity
    {
        $source = $this->storage->getByCode($sourceCode);
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
        );
        $this->storage->put($copy);

        return $copy;
    }

    /**
     * @param list<string> $productCodes
     */
    #[Override]
    public function bulkUpdateStatus(array $productCodes, int $newStatus): int
    {
        $changed = 0;
        foreach ($productCodes as $code) {
            $current = $this->storage->getByCode($code);
            if ($current === null) {
                continue;
            }

            if ($current->productStatus === $newStatus) {
                continue;
            }

            $this->storage->put(new ProductEntity(
                productCode: $current->productCode,
                productName: $current->productName,
                price02: $current->price02,
                stock: $current->stock,
                productStatus: $newStatus,
                description: $current->description,
                searchWord: $current->searchWord,
                note: $current->note,
            ));
            $changed++;
        }

        return $changed;
    }
}
