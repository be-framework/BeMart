<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Support\ProductImageCatalog;
use BEAR\Resource\Annotation\JsonSchema;

use function array_filter;
use function array_map;
use function array_slice;
use function array_values;
use function count;
use function in_array;
use function max;
use function min;
use function usort;

/**
 * EC-CUBE goProductList — 商品一覧ページ.
 *
 * Anonymous-accessible (returns 200 regardless of session state). Maps
 * to `page://self/products`, the target of the `goProductList`
 * transition declared on Index / Login and of the storefront header
 * search block.
 *
 * Earlier Phase 3 rendered only the empty-result scaffold. That kept the
 * EC-CUBE template port small but made the top-page "全ての商品" flow a
 * dead end. This resource now reads the existing ProductQuery corpus,
 * filters to public products, and projects the small row shape expected
 * by the ported EC-CUBE `Product/list.twig`.
 *
 * @see #ProductList in alps.json
 */
class Products extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery,
        private readonly CsrfToken $csrf,
    ) {
    }

    /**
     * EC-CUBE goProductList — render the product-list page.
     *
     * `name` is the EC-CUBE header search field. `nameKeyword` is accepted
     * as a BeMart/API-friendly alias.
     *
     * @psalm-taint-source input $name
     * @psalm-taint-source input $nameKeyword
     */
    #[Alps('goProductList')]
    #[JsonSchema(schema: 'get-products.json', params: 'get-products.param.json')]
    #[Link(rel: 'goProduct', href: 'page://self/product')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(
        string|null $name = null,
        string|null $nameKeyword = null,
        int $limit = 50,
        int $offset = 0,
        string|null $category_id = null,
        string|null $pageno = null,
        string|null $disp_number = null,
        string|null $orderby = null,
    ): static
    {
        $keyword = $nameKeyword ?? $name;
        $dispNumber = $this->normalizeDispNumber($disp_number, $limit);
        $pageNo = max(1, (int) ($pageno ?? '1'));

        $products = $keyword === null || $keyword === ''
            ? $this->productQuery->list(500, 0)
            : $this->productQuery->search($keyword, 500);

        $visibleProducts = array_values(array_filter(
            $products,
            static fn (ProductEntity $product): bool => $product->productStatus === ProductEntity::STATUS_VISIBLE,
        ));
        $visibleProducts = $this->filterByCategory($visibleProducts, $category_id);
        $this->sortProducts($visibleProducts, $orderby);
        $totalItemCount = count($visibleProducts);
        $pagedProducts = array_slice(
            $visibleProducts,
            $offset > 0 ? $offset : (($pageNo - 1) * $dispNumber),
            $dispNumber,
        );

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goProductList',
            'totalItemCount' => $totalItemCount,
            'products' => array_map($this->productRow(...), $pagedProducts),
            'csrfToken' => $this->csrf->token,
            'filters' => [
                'name' => $name,
                'nameKeyword' => $nameKeyword,
                'category_id' => $category_id,
                'categoryName' => $this->categoryName($category_id),
                'disp_number' => (string) $dispNumber,
                'orderby' => $orderby ?? '',
                'pageno' => (string) $pageNo,
            ],
            'pager' => $this->pager($totalItemCount, $pageNo, $dispNumber),
        ];

        return $this;
    }

    /** @return array{id: string, productCode: string, name: string, productName: string, price02: int, stock: int|null, stockFind: bool, descriptionList: string, mainListImage: string, categoryNames: list<string>, tagNames: list<string>} */
    private function productRow(ProductEntity $product): array
    {
        return [
            // EC-CUBE templates call this `Product.id`. In BeMart the
            // stable public identity is the product code, so expose it
            // under both names for the template and for JSON clients.
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

    /**
     * @param list<ProductEntity> $products
     *
     * @return list<ProductEntity>
     */
    private function filterByCategory(array $products, string|null $categoryId): array
    {
        $categoryName = $this->categoryName($categoryId);
        if ($categoryName === null) {
            return $products;
        }

        return array_values(array_filter(
            $products,
            static fn (ProductEntity $product): bool => in_array($categoryName, $product->categoryNames, true),
        ));
    }

    /** @param list<ProductEntity> $products */
    private function sortProducts(array &$products, string|null $orderby): void
    {
        if ($orderby === 'price_low' || $orderby === 'price_asc' || $orderby === '2') {
            usort($products, static fn (ProductEntity $a, ProductEntity $b): int => $a->price02 <=> $b->price02);

            return;
        }

        if ($orderby === 'price_high' || $orderby === 'price_desc' || $orderby === '3') {
            usort($products, static fn (ProductEntity $a, ProductEntity $b): int => $b->price02 <=> $a->price02);

            return;
        }

        // EC-CUBE's default order is configured in master data. BeMart's
        // fake corpus keeps insertion order as the deterministic default.
    }

    private function normalizeDispNumber(string|null $dispNumber, int $fallback): int
    {
        $value = (int) ($dispNumber ?? (string) min(20, max(1, $fallback)));

        return in_array($value, [20, 40, 60], true) ? $value : 20;
    }

    private function categoryName(string|null $categoryId): string|null
    {
        return match ($categoryId) {
            '1' => 'ジェラート',
            '2' => '新入荷',
            '3' => '彩のデザート',
            '4' => 'CUBE',
            '5' => 'アイスサンド',
            '6' => 'フルーツ',
            default => null,
        };
    }

    /** @return array{current: int, pageCount: int, previous: int|null, next: int|null, pages: list<int>} */
    private function pager(int $totalItemCount, int $pageNo, int $dispNumber): array
    {
        $pageCount = max(1, (int) ceil($totalItemCount / $dispNumber));
        $current = min(max(1, $pageNo), $pageCount);

        return [
            'current' => $current,
            'pageCount' => $pageCount,
            'previous' => $current > 1 ? $current - 1 : null,
            'next' => $current < $pageCount ? $current + 1 : null,
            'pages' => range(1, $pageCount),
        ];
    }
}
