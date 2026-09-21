<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\App\Product;

use BEAR\QueryRepository\Header;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Resource\App\Products;

/**
 * The part of a product that moves: how many are left
 *
 * Product master data changes when someone edits it; stock changes when someone buys. Keeping
 * them in one cache entry means the slow-moving part is thrown away at the speed of the fast one.
 * This resource carries the fast part alone, so `app://self/product` can embed it and be
 * invalidated through it - purging this URI drops the product entry with it, because the parent is
 * stored under this resource's tag. It also carries the corpus tag directly: every writer of stock
 * announces `product-corpus`, not this URI, so an admin edit has to reach this entry the same way
 * it reaches the rest of the corpus, or a re-embed after the parent's own purge still hands back
 * the stale number.
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
        $this->headers[Header::SURROGATE_KEY] = Products::SURROGATE_KEY;
        $this->body = [
            'productCode' => $product->productCode,
            'stock' => $product->stock,
            // null stock means the product is not stock-managed, which is in stock by definition
            'stockFind' => $product->stock === null || $product->stock > 0,
        ];

        return $this;
    }
}
