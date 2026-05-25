<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function count;

/**
 * Product list fetched — Final, admin-side product grid projection.
 *
 *   GetProductListInput → ProductListFetched  (Direct, safe read)
 *
 * AUTHZ — admin firewall (Wave 4 contract, parity with Wave 5M
 * CustomerListFetched):
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess
 *
 * Admin-only endpoint. The customer-facing product list (when it
 * lands) will be a sibling Final that filters on
 * `productStatus === STATUS_VISIBLE`; this admin variant walks every
 * status including STATUS_WITHDRAWN so the operator can audit /
 * restore soft-deleted rows.
 *
 * Filter scope (Wave 8 first iteration):
 *   - nameKeyword — substring match on productName (null = disabled)
 *   - limit + offset — pagination (Limit + Offset Semantic bounds)
 *
 * TODO(Phase 2): EC-CUBE's admin form additionally filters by category,
 * tag, stock state, sale type. Same scope-deferral discipline as
 * Wave 5M CustomerListFetched.
 *
 * Public surface — shallow projection of ProductEntity, dropping the
 * descriptive blobs (description / searchWord / note) since the admin
 * grid only needs the identification + headline columns. The full
 * entity is available via the per-product `goProduct` detail page.
 */
final readonly class ProductListFetched
{
    /** @var list<array{productCode: string, productName: string, price02: int, stock: int|null, productStatus: int, imagePath: string|null, categoryNames: list<string>, tagNames: list<string>}> */
    public array $products;

    public int $count;

    /** @var array{nameKeyword: string|null, limit: int, offset: int} */
    public array $filters;

    public function __construct(
        #[Input] string|null $nameKeyword,
        #[Input] int $limit,
        #[Input] int $offset,
        #[Inject] AdminSession $adminSession,
        #[Inject] ProductQueryInterface $productQuery,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        // The keyword filter is the dominant axis; pagination is
        // applied via the offset/limit knobs. When a keyword is
        // present we use search() (which scans and caps); when it is
        // null we use listAll() so the offset cursor works.
        $rows = $nameKeyword !== null && $nameKeyword !== ''
            ? $productQuery->search($nameKeyword, $limit)
            : $productQuery->list($limit, $offset);

        $this->products = array_map(
            static fn (ProductEntity $p): array => [
                'productCode' => $p->productCode,
                'productName' => $p->productName,
                'price02' => $p->price02,
                'stock' => $p->stock,
                'productStatus' => $p->productStatus,
                'imagePath' => $p->imagePath,
                'categoryNames' => $p->categoryNames,
                'tagNames' => $p->tagNames,
            ],
            $rows,
        );
        $this->count = count($rows);
        $this->filters = [
            'nameKeyword' => $nameKeyword,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }
}
