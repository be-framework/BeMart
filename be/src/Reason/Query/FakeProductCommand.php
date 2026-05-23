<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\Param\ProductCodeList;
use MyVendor\BeMart\Be\Reason\Query\Result\CopiedProduct;
use MyVendor\BeMart\Be\Reason\Query\Result\ProductStatusUpdate;
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
            imagePath: $current->imagePath,
            categoryNames: $current->categoryNames,
            tagNames: $current->tagNames,
            classNames: $current->classNames,
        ));
    }

    #[Override]
    public function copy(string $sourceCode, string $newCode): CopiedProduct
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
            imagePath: $source->imagePath,
            categoryNames: $source->categoryNames,
            tagNames: $source->tagNames,
            classNames: $source->classNames,
        );
        $this->storage->put($copy);

        return new CopiedProduct($copy);
    }

    /**
     */
    #[Override]
    public function bulkUpdateStatus(ProductCodeList $productCodes, int $newStatus): ProductStatusUpdate
    {
        $changed = 0;
        foreach ($productCodes->values() as $code) {
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
                imagePath: $current->imagePath,
                categoryNames: $current->categoryNames,
                tagNames: $current->tagNames,
                classNames: $current->classNames,
            ));
            $changed++;
        }

        return new ProductStatusUpdate($changed);
    }
}
