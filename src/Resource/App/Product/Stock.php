<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\App\Product;

use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;

/**
 * The part of a product that moves: how many are left
 *
 * Product master data changes when someone edits it; stock changes when someone buys. Keeping
 * them in one cache entry means the slow-moving part is thrown away at the speed of the fast one.
 * This resource carries the fast part alone, so `app://self/product` can embed it and be
 * invalidated through it - purging this URI drops the product entry with it, because the parent is
 * stored under this resource's tag.
 */
#[Cacheable(expirySecond: 30)]
class Stock extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery,
    ) {
    }

    public function onGet(string $productCode): static
    {
        $product = $this->productQuery->item($productCode);
        if (! $product instanceof ProductEntity) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['productCode' => $productCode];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'productCode' => $product->productCode,
            'stock' => $product->stock,
            // null stock means the product is not stock-managed, which is in stock by definition
            'stockFind' => $product->stock === null || $product->stock > 0,
        ];

        return $this;
    }
}
