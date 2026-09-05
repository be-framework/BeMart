<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\App;

use BEAR\QueryRepository\Header;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Support\ProductImageCatalog;

/**
 * One product's master data, with its stock embedded rather than copied
 *
 * The embed is what makes the dependency real: this entry is stored under the stock resource's
 * tag as well as its own, so purging the stock URI drops this entry too. Copying the number in
 * would have made a stale product page that no invalidation could reach.
 *
 * Master data changes when someone edits a product; until an admin write path purges this URI,
 * the cascade through stock and the 30-second TTL are the eviction paths. A bare `#[Cacheable]`
 * would be a year, and a parent hit never re-reads the embedded stock.
 */
#[Cacheable(expirySecond: 30)]
class Product extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery,
    ) {
    }

    #[Embed(rel: 'stock', src: 'app://self/product/stock{?productCode}')]
    public function onGet(string $productCode): static
    {
        $product = $this->productQuery->item($productCode);
        if (! $product instanceof ProductEntity || $product->productStatus !== ProductEntity::STATUS_VISIBLE) {
            $this->code = Code::NOT_FOUND;
            $this->body['productCode'] = $productCode;

            return $this;
        }

        $this->code = Code::OK;
        // The same shared tag the corpus carries: an admin edit drops both with one call
        $this->headers[Header::SURROGATE_KEY] = Products::SURROGATE_KEY;
        $this->body['productCode'] = $product->productCode;
        $this->body['productName'] = $product->productName;
        $this->body['price02'] = $product->price02;
        $this->body['description'] = $product->description ?? '';
        $this->body['categoryNames'] = $product->categoryNames;
        $this->body['tagNames'] = $product->tagNames;
        $this->body['classNames'] = $product->classNames;
        $this->body['image'] = $product->imagePath ?? ProductImageCatalog::forProductCode($product->productCode);

        return $this;
    }
}
