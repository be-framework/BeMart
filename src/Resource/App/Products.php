<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\App;

use BEAR\QueryRepository\Header;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Support\ProductImageCatalog;

use function array_filter;
use function array_map;
use function array_values;

/**
 * The visible product corpus, as rows a page can render
 *
 * The storefront pages used to query the database themselves, which put the expensive part of a
 * request outside the resource graph: nothing could cache it and nothing could say what a page
 * depended on. This resource owns the query and the row shape; pages embed it and do their own
 * per-request work (search field aliases, category filter, sort, pagination, CSRF).
 *
 * Two invalidation paths, because one alone is not enough. Every entry declares the shared
 * `product-corpus` surrogate key, so an admin edit can drop every keyword and limit variant with
 * one tag - the URI tag would only reach the exact query string that produced an entry. The TTL is
 * the floor under that: `stock` moves as orders are placed, and no write path announces those.
 */
#[Cacheable]
class Products extends ResourceObject
{
    /** The tag every variant of this corpus carries, so one purge reaches all of them */
    public const SURROGATE_KEY = 'product-corpus';

    public function __construct(
        private readonly ProductQueryInterface $productQuery,
    ) {
    }

    /**
     * @param string|null $nameKeyword product name keyword
     * @param string|null $name        the storefront's own field name for the same keyword
     */
    public function onGet(string|null $nameKeyword = null, string|null $name = null, int $limit = 500): static
    {
        $keyword = $nameKeyword ?? $name;
        $products = $keyword === null || $keyword === ''
            ? $this->productQuery->list($limit, 0)
            : $this->productQuery->search($keyword, $limit);

        $visible = array_values(array_filter(
            $products,
            static fn (ProductEntity $product): bool => $product->productStatus === ProductEntity::STATUS_VISIBLE,
        ));

        $this->code = Code::OK;
        $this->headers[Header::SURROGATE_KEY] = self::SURROGATE_KEY;
        $this->body = ['products' => array_map($this->row(...), $visible)];

        return $this;
    }

    /** @return array{id: string, productCode: string, name: string, productName: string, price02: int, stock: int|null, stockFind: bool, descriptionList: string, mainListImage: string, categoryNames: list<string>, tagNames: list<string>} */
    private function row(ProductEntity $product): array
    {
        return [
            // EC-CUBE templates call this `Product.id`; the stable public identity is the code,
            // so both names carry it.
            'id' => $product->productCode,
            'productCode' => $product->productCode,
            'name' => $product->productName,
            'productName' => $product->productName,
            'price02' => $product->price02,
            'stock' => $product->stock,
            'stockFind' => $product->stock === null || $product->stock > 0,
            'descriptionList' => $product->description ?? '',
            'mainListImage' => $product->imagePath ?? ProductImageCatalog::forProductCode($product->productCode),
            'categoryNames' => $product->categoryNames,
            'tagNames' => $product->tagNames,
        ];
    }
}
