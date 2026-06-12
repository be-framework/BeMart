<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\Annotation\JsonSchema;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Entity\ProductReviewSummary;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductReviewQueryInterface;
use MyVendor\BeMart\Support\ProductImageCatalog;

use function array_filter;
use function array_map;
use function array_slice;
use function array_values;

/**
 * EC-CUBE goTop — トップページ (Wave 3H pure renderer).
 *
 * Pure renderer: no Be Framework, no domain logic, no Reasons.
 * Anonymous-accessible (returns 200 regardless of session state).
 * Maps to `page://self/`.
 *
 * The ALPS `#Top` resource lists 13 descriptors. In the production
 * frontend these are populated via Twig / EC-CUBE side queries (shop
 * message, news, recommended products, category nav, etc.). Wave 3H
 * deliberately limits this renderer to the link surface and a stub
 * `staticContent` shape — full data lookup (shop message, news,
 * recommended products, category navigation) is deferred and noted
 * inline as TODO until a dedicated Top aggregation lands.
 *
 * @see #Top in alps.json — full descriptor list
 */
class Index extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery,
        private readonly ProductReviewQueryInterface $productReviewQuery,
    ) {
    }

    /**
     * EC-CUBE goTop — render the top page scaffolding.
     *
     * @todo Wave-future: aggregate shopMessage, newArrivals,
     *     recommendedProducts, categoryNav into `body`. For now the
     *     resource declares the hypermedia surface only.
     */
    #[Alps('goTop')]
    #[JsonSchema(schema: 'get-index.json')]
    #[Link(rel: 'goProductList', href: 'page://self/products')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[Link(rel: 'goContactForm', href: 'page://self/contact')]
    #[Link(rel: 'goCustomerRegistration', href: 'page://self/entry')]
    #[Link(rel: 'goLogin', href: 'page://self/login')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    #[Link(rel: 'goHelpAbout', href: 'page://self/help/about')]
    #[Link(rel: 'goHelpGuide', href: 'page://self/help/guide')]
    #[Link(rel: 'goHelpAgreement', href: 'page://self/help/agreement')]
    #[Link(rel: 'goHelpPrivacy', href: 'page://self/help/privacy')]
    #[Link(rel: 'goHelpTradeLaw', href: 'page://self/help/trade-law')]
    public function onGet(): static
    {
        $products = array_slice(array_values(array_filter(
            $this->productQuery->list(40, 0),
            static fn (ProductEntity $product): bool => $product->productStatus === ProductEntity::STATUS_VISIBLE,
        )), 0, 20);

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goTop',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => null,
            'featuredProducts' => array_map($this->productRow(...), $products),
            'links' => [
                'goProductList' => 'page://self/products',
                'goCart' => 'page://self/cart',
                'goContactForm' => 'page://self/contact',
                'goCustomerRegistration' => 'page://self/entry',
                'goLogin' => 'page://self/login',
                'goMypage' => 'page://self/mypage',
                'goHelpAbout' => 'page://self/help/about',
                'goHelpGuide' => 'page://self/help/guide',
                'goHelpAgreement' => 'page://self/help/agreement',
                'goHelpPrivacy' => 'page://self/help/privacy',
                'goHelpTradeLaw' => 'page://self/help/trade-law',
            ],
        ];

        return $this;
    }

    /** @return array{id: string, productCode: string, name: string, productName: string, price02: int, stock: int|null, stockFind: bool, descriptionList: string, mainListImage: string, categoryNames: list<string>, tagNames: list<string>, reviewSummary: array{averageRating: float|null, reviewCount: int}} */
    private function productRow(ProductEntity $product): array
    {
        return [
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
            'reviewSummary' => $this->reviewSummary($product->productCode),
        ];
    }

    /** @return array{averageRating: float|null, reviewCount: int} */
    private function reviewSummary(string $productCode): array
    {
        $summary = $this->productReviewQuery->summaryByProduct($productCode);
        if (! $summary instanceof ProductReviewSummary || $summary->reviewCount <= 0) {
            return ['averageRating' => null, 'reviewCount' => 0];
        }

        return [
            'averageRating' => $summary->averageRating,
            'reviewCount' => $summary->reviewCount,
        ];
    }
}
