<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * A product fetched from the catalog.
 *
 * Existence of this object proves: (1) productCode is well-formed
 * (Semantic\ProductCode passed), (2) a row was found
 * (ProductNotFoundException otherwise). Resources can read fields
 * directly without re-validation.
 */
final readonly class ProductFetched
{
    public string $productCode;
    public string $productName;
    public int $price02;
    public int|null $stock;

    public function __construct(
        #[Input] string $productCode,
        #[Inject] ProductQueryInterface $productQuery,
    ) {
        $entity = $productQuery->item($productCode);
        if (! $entity instanceof ProductEntity) {
            throw new ProductNotFoundException();
        }

        $this->productCode = $entity->productCode;
        $this->productName = $entity->productName;
        $this->price02 = $entity->price02;
        $this->stock = $entity->stock;
    }
}
