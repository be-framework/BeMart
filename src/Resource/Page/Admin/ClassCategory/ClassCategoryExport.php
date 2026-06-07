<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\ClassCategory;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ClassCategoryCsvExported;
use MyVendor\BeMart\Be\Input\ExportClassCategoryInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE 規格分類CSVダウンロード (goExportClassCategory).
 *
 *   GET/POST /admin_product_class_category_export → CSV download
 *
 * `onGet` drives the Be `goExportClassCategory` transition (optionally
 * scoped to one 規格名); the EC-CUBE-format encoding + download headers
 * are isolated behind
 * {@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.
 */
class ClassCategoryExport extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `goExportClassCategory` に対応する GET 操作。
     * @psalm-taint-source input $classNameId
     */
    #[Alps('goExportClassCategory')]
    #[JsonSchema(schema: 'get-admin-class-category-class-category-export.json', params: 'get-admin-class-category-class-category-export.param.json')]
    #[Link(rel: 'doImportClassCategoryCsv', href: 'page://self/admin/product/csv-class-category', method: 'post')]
    public function onGet(string|null $classNameId = null): static
    {
        $final = ($this->becoming)(new ExportClassCategoryInput(classNameId: $classNameId));

        assert($final instanceof ClassCategoryCsvExported);

        $this->code = Code::OK;
        // The CsvDocument bytes are UTF-8 (built from PHP UTF-8 strings);
        // advertise that actual charset — like the sibling CSV exporters
        // (ProductCsv / CustomerCsv / Category\Csv / Order exports) — so
        // Japanese 規格分類名 decode correctly instead of mis-declaring Shift_JIS.
        $this->headers['Content-Type'] = 'text/csv; charset=UTF-8';
        $this->headers['Content-Disposition'] = $final->document->contentDisposition;
        $this->body = $final->document->content;

        return $this;
    }
}
