<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\App\Agent;

use BEAR\QueryRepository\Header;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\ToolUse\Attribute\Tool;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Resource\App\Products;
use MyVendor\BeMart\Support\ProductImageCatalog;

use function array_filter;
use function array_map;
use function array_slice;
use function array_values;
use function max;
use function min;

/**
 * LLM-readable catalogue search for BEAR.ToolUse agents
 *
 * The `product-corpus` tag reaches an admin edit; the `short` TTL is the floor under the stock
 * number copied into each row, which no write path announces.
 */
#[Cacheable(expiry: 'short')]
class Catalog extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery,
    ) {
    }

    /**
     * Search visible products for an agent.
     *
     * @param string|null $nameKeyword Product name keyword. Omit to list the first products.
     * @param int         $limit       Maximum number of products to return. 1-20.
     */
    #[Tool(name: 'catalog_search', description: 'Search the visible BeMart product catalogue for an AI agent. Returns compact product rows.')]
    public function onGet(string|null $nameKeyword = null, int $limit = 5): static
    {
        $limit = min(20, max(1, $limit));
        $products = $nameKeyword === null || $nameKeyword === ''
            ? $this->productQuery->list($limit, 0)
            : $this->productQuery->search($nameKeyword, $limit);

        $visibleProducts = array_slice(array_values(array_filter(
            $products,
            static fn (ProductEntity $product): bool => $product->productStatus === ProductEntity::STATUS_VISIBLE,
        )), 0, $limit);

        $this->code = Code::OK;
        $this->headers[Header::SURROGATE_KEY] = Products::SURROGATE_KEY;
        $this->body = [
            'query' => $nameKeyword,
            'count' => count($visibleProducts),
            'products' => array_map($this->row(...), $visibleProducts),
        ];

        return $this;
    }

    /** @return array{productCode: string, productName: string, price02: int, stock: int|null, description: string, categories: list<string>, tags: list<string>, image: string} */
    private function row(ProductEntity $product): array
    {
        return [
            'productCode' => $product->productCode,
            'productName' => $product->productName,
            'price02' => $product->price02,
            'stock' => $product->stock,
            'description' => $product->description ?? '',
            'categories' => $product->categoryNames,
            'tags' => $product->tagNames,
            'image' => $product->imagePath ?? ProductImageCatalog::forProductCode($product->productCode),
        ];
    }
}
