<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ProductListFetched;
use MyVendor\BeMart\Be\Input\GetProductListInput;

use function assert;

/**
 * EC-CUBE goProductList — 商品一覧（管理画面） (Wave 8, admin filter
 * search + pagination).
 *
 * Safe read. No CSRF (read-only). Admin-only — the Be Final raises
 * UnauthorizedAdminAccessException when AdminSessionInterface reports
 * no admin session, which we map to 403. The customer-facing product
 * list (when it lands) will be a sibling resource at a different URL.
 *
 * Failure mapping:
 *   - SemanticVariableException             → 400 (filter format invalid)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *
 * Hypermedia: links to per-product admin detail + CSV export + bulk
 * status update endpoints — the operator drills into a row from the
 * grid, exports the corpus, or applies a bulk action.
 */
class ProductList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * @psalm-taint-source input $nameKeyword
     * @psalm-taint-source input $limit
     * @psalm-taint-source input $offset
     */
    #[Link(rel: 'goProduct', href: 'page://self/admin/product', method: 'get')]
    #[Link(rel: 'doCreateProduct', href: 'page://self/admin/product', method: 'post')]
    #[Link(rel: 'doBulkUpdateProductStatus', href: 'page://self/admin/product-bulk-status', method: 'post')]
    #[Link(rel: 'goExportProduct', href: 'page://self/admin/product-csv', method: 'get')]
    public function onGet(
        string|null $nameKeyword = null,
        int $limit = 50,
        int $offset = 0,
    ): static {
        try {
            $final = ($this->becoming)(new GetProductListInput(
                nameKeyword: $nameKeyword,
                limit: $limit,
                offset: $offset,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof ProductListFetched);

        $this->code = Code::OK;
        $this->body = [
            'products' => $final->products,
            'count' => $final->count,
            'filters' => $final->filters,
        ];

        return $this;
    }
}
