<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use Ray\Di\Di\Inject;

use function array_filter;
use function array_map;
use function array_values;
use function count;

/**
 * Storefront product list fetched — Final, customer-facing catalog
 * projection.
 *
 *   GetStorefrontProductListInput → StorefrontProductListFetched
 *   (Direct, safe read)
 *
 * Anonymous-accessible — NO admin firewall (the customer-facing sibling
 * of {@see ProductListFetched}, which gates on an admin session and
 * walks every status). This Final projects ONLY
 * `productStatus === ProductEntity::STATUS_VISIBLE`: 非公開 (hidden) and
 * 廃止 (withdrawn) products are never exposed to the storefront. Filtering
 * at this call site is the projection the {@see ProductQueryInterface}
 * contract sanctions for the customer-facing list.
 *
 * Public surface — the projection the storefront `Product/list.twig`
 * port reads: each row is `{id, name, price02}`. `id` carries the
 * productCode (the `product_detail` route's `{id}` placeholder resolves
 * to `productCode`), so every rendered grid link resolves back through
 * the shared RouteTable.
 *
 * Scope (厳密移植 Grade-C, parity with the Cart port): EC-CUBE's
 * `list.twig` catalog row also carries a thumbnail image, a
 * ProductClass-derived price RANGE and a per-item add-cart form. The
 * BeMart body carries neither product imagery nor ProductClass joins,
 * so the port renders the bare name + single `price02`; those omissions
 * are enumerated residuals in ProductsHtmlRenderTest. Pagination /
 * category / keyword filtering is deferred to Phase 2.
 */
final readonly class StorefrontProductListFetched
{
    /**
     * Storefront catalog page cap. The Fake corpus is tiny and the SQL
     * corpus is a PoC dataset; a real cursor / pagination is the
     * Phase 2 extension flagged on {@see GetStorefrontProductListInput}.
     */
    private const int CATALOG_LIMIT = 1000;

    /** @var list<array{id: string, name: string, price02: int}> */
    public array $products;

    public int $totalItemCount;

    public function __construct(
        #[Inject] ProductQueryInterface $productQuery,
    ) {
        $visible = array_values(array_filter(
            $productQuery->listAll(self::CATALOG_LIMIT),
            static fn (ProductEntity $p): bool => $p->productStatus === ProductEntity::STATUS_VISIBLE,
        ));

        $this->products = array_map(
            static fn (ProductEntity $p): array => [
                'id' => $p->productCode,
                'name' => $p->productName,
                'price02' => $p->price02,
            ],
            $visible,
        );
        $this->totalItemCount = count($visible);
    }
}
