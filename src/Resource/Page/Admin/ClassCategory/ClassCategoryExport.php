<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\ClassCategory;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ClassCategoryCsvExported;
use MyVendor\BeMart\Be\Input\ExportClassCategoryInput;

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
     * @psalm-taint-source input $classNameId
     */
    #[Link(rel: 'doImportClassCategoryCsv', href: 'page://self/admin/product/csv-class-category', method: 'post')]
    public function onGet(string|null $classNameId = null): static
    {
        try {
            $final = ($this->becoming)(new ExportClassCategoryInput(classNameId: $classNameId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

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
