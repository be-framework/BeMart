<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Final\StorefrontProductListFetched;
use MyVendor\BeMart\Be\Input\GetStorefrontProductListInput;

use function assert;

/**
 * EC-CUBE goProductList — 商品一覧ページ。
 *
 * Resource is the HTTP entry point: it builds a Be Input, hands it to
 * Becoming, and projects the resulting Final into the response body —
 * the same shape as {@see Product} (the detail page). All catalog
 * access lives in the Be domain layer.
 *
 * Anonymous-accessible (returns 200 regardless of session state). Maps
 * to `page://self/products`, the target of the `goProductList`
 * transition declared on Index / Login.
 *
 * The Becoming chain (`GetStorefrontProductListInput` →
 * `StorefrontProductListFetched`) is the customer-facing sibling of the
 * admin product grid: it has no admin firewall and projects only
 * STATUS_VISIBLE products. The ported template
 * (var/templates/Page/Products.html.twig) renders the populated
 * `ec-shelfGrid` branch of EC-CUBE's `Product/list.twig` when
 * `totalItemCount > 0`, and the "お探しの商品は見つかりませんでした"
 * empty-result branch otherwise.
 *
 * @see #ProductList in alps.json
 */
class Products extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * EC-CUBE goProductList — render the storefront product-list page.
     *
     * @todo Phase 2: category / keyword / tag filters + pagination,
     *     plus a ProductClass-aware price range and product imagery,
     *     per EC-CUBE's `Product/list.twig` search form.
     */
    #[Link(rel: 'goProduct', href: 'page://self/product')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetStorefrontProductListInput());
        assert($final instanceof StorefrontProductListFetched);

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goProductList',
            'totalItemCount' => $final->totalItemCount,
            'products' => $final->products,
            'links' => [
                'goProduct' => 'page://self/product',
                'goCart' => 'page://self/cart',
                'goTop' => 'page://self/',
            ],
        ];

        return $this;
    }
}
