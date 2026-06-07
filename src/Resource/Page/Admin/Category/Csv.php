<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Category;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\CategoryCsvExported;
use MyVendor\BeMart\Be\Final\CategoryCsvImported;
use MyVendor\BeMart\Be\Input\ExportCategoryInput;
use MyVendor\BeMart\Be\Input\ImportCategoryCsvInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goExportCategory + doImportCategoryCsv — CSV endpoint
 * (Wave 7).
 *
 *   - GET  → goExportCategory   (RFC 4180 dump — admin AUTHZ)
 *   - POST → doImportCategoryCsv (**Phase 2 stub** — accepts the body
 *                                 but does not persist; ALPS/AUTHZ
 *                                 contract is exercised, full parser
 *                                 deferred)
 *
 * Both methods enforce the admin firewall. The stubbed import path
 * returns `accepted=false` with an explanatory notice so callers
 * cannot mistake the stub for a real import.
 */
class Csv extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /** ALPS `goExportCategory` に対応する GET 操作。 */
    #[Alps('goExportCategory')]
    #[JsonSchema(schema: 'get-admin-category-csv.json')]
    #[Link(rel: 'goCategoryList', href: 'page://self/admin/category/category-list')]
    #[Link(rel: 'doImportCategoryCsv', href: 'page://self/admin/category/csv', method: 'post')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new ExportCategoryInput());

        assert($final instanceof CategoryCsvExported);

        $this->code = Code::OK;
        $this->headers['Content-Type'] = 'text/csv; charset=UTF-8';
        $this->body = [
            'csv' => $final->csv,
            'rowCount' => $final->rowCount,
        ];

        return $this;
    }

    /**
     * ALPS `doImportCategoryCsv` に対応する POST 操作。
     * @psalm-taint-source input $csv
     */
    #[Alps('doImportCategoryCsv')]
    #[JsonSchema(schema: 'post-admin-category-csv.json', params: 'post-admin-category-csv.param.json')]
    #[Link(rel: 'goCategoryList', href: 'page://self/admin/category/category-list')]
    #[Link(rel: 'goExportOrder', href: 'page://self/admin/order/export-order')]
    #[CsrfProtected]
    public function onPost(string $csv): static
    {
        $final = ($this->becoming)(new ImportCategoryCsvInput(csv: $csv));

        assert($final instanceof CategoryCsvImported);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin/product/category';
        $this->body = [
            'transitionId' => 'doImportCategoryCsv',
            'accepted' => $final->accepted,
            'lineCount' => $final->lineCount,
            'imported' => $final->imported,
            'deleted' => $final->deleted,
            'message' => $final->message,
        ];

        return $this;
    }
}
