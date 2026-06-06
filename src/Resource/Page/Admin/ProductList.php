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
use MyVendor\BeMart\Form\AdminProductSearchForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE goProductList — 商品一覧（管理画面） (Wave 8, admin filter
 * search + pagination).
 *
 * Safe read. No CSRF (read-only). Admin-only — the Be Final raises
 * UnauthorizedAdminAccessException when AdminSession reports
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
        private readonly FormFactory $formFactory,
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
        $final = ($this->becoming)(new GetProductListInput(
            nameKeyword: $nameKeyword,
            limit: $limit,
            offset: $offset,
        ));

        assert($final instanceof ProductListFetched);

        $this->code = Code::OK;
        $this->body = [
            'products' => $final->products,
            'count' => $final->count,
            'filters' => $final->filters,
        ];
        // Phase 3: an AdminProductSearchForm for the HTML list page to
        // render the keyword box via `{{ searchForm.input(...) }}`,
        // re-filled with the active filter. JSON contexts ignore it.
        $searchForm = $this->formFactory->newInstance(AdminProductSearchForm::class);
        assert($searchForm instanceof AdminProductSearchForm);
        $searchForm->fillFilters($final->filters);
        $this->body['searchForm'] = $searchForm;

        return $this;
    }
}
