<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goProductList — 商品一覧ページ (Phase 3 pure renderer).
 *
 * Pure renderer: no Be Framework, no domain logic, no Reasons — the
 * same shape as {@see Index}. Anonymous-accessible (returns 200
 * regardless of session state). Maps to `page://self/products`, the
 * target of the `goProductList` transition declared on Index / Login.
 *
 * The ALPS `#ProductList` descriptor is a state composed of `#Product`
 * items plus the catalog navigation transitions; it carries no
 * list-level data vocabulary of its own. A faithful product LISTING
 * needs a real catalog query (filter by category / keyword / tag /
 * stock, pagination) — that is a vertical slice (Entity + SQL), out of
 * scope for this template-porting wave. This renderer therefore
 * declares the hypermedia surface and an EMPTY `products` list; the
 * ported template (var/templates/Page/Products.html.twig) renders the
 * "お探しの商品は見つかりませんでした" empty-result branch of EC-CUBE's
 * `Product/list.twig`. The populated-list branch is flagged for a
 * follow-up ProductList vertical-slice enrichment.
 *
 * @see #ProductList in alps.json
 */
class Products extends ResourceObject
{
    /**
     * EC-CUBE goProductList — render the product-list page scaffolding.
     *
     * @todo Wave-future: a ProductList vertical slice (catalog query +
     *     pagination) populates `products`; the template's populated
     *     `ec-shelfGrid` branch then renders real items.
     */
    #[Link(rel: 'goProduct', href: 'page://self/product')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goProductList',
            'totalItemCount' => 0,
            'products' => [],
            'links' => [
                'goProduct' => 'page://self/product',
                'goCart' => 'page://self/cart',
                'goTop' => 'page://self/',
            ],
        ];

        return $this;
    }
}
