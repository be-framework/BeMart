<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\ClassName;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ClassNameCsvExported;
use MyVendor\BeMart\Be\Input\ExportClassNameInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE 規格名CSVダウンロード (goExportClassName).
 *
 *   GET/POST /admin/class-name/class-name-export → CSV download
 *
 * `onGet` drives the Be `goExportClassName` transition; the EC-CUBE-format
 * encoding + download headers are isolated behind
 * {@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.
 */
class ClassNameExport extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /** ALPS `goExportClassName` に対応する GET 操作。 */
    #[Alps('goExportClassName')]
    #[JsonSchema(schema: 'get-admin-class-name-class-name-export.json')]
    #[Link(rel: 'doImportClassNameCsv', href: 'page://self/admin/product/csv-class-name', method: 'post')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new ExportClassNameInput());

        assert($final instanceof ClassNameCsvExported);

        $this->code = Code::OK;
        // The CsvDocument bytes are UTF-8 (built from PHP UTF-8 strings);
        // advertise that actual charset — like the sibling CSV exporters
        // (ProductCsv / CustomerCsv / Category\Csv / Order exports) — so
        // Japanese 規格名 decode correctly instead of mis-declaring Shift_JIS.
        $this->headers['Content-Type'] = 'text/csv; charset=UTF-8';
        $this->headers['Content-Disposition'] = $final->document->contentDisposition;
        $this->body = $final->document->content;

        return $this;
    }
}
