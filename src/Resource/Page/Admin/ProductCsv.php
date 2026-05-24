<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductCsvExported;
use MyVendor\BeMart\Be\Input\AdminExportProductInput;

use function assert;

/**
 * EC-CUBE goExportProduct — 商品CSVをエクスポートする (Wave 8 admin).
 *
 * onGet only — safe download. Admin-only.
 *
 * ALPS counterpart `doImportProductCsv` is INTENTIONALLY NOT
 * implemented in Wave 8: the EC-CUBE importer parses dtb_product
 * shaped CSV rows with insert-or-update semantics + multi-column
 * uniqueness contracts + extended PurchaseFlow orchestration. That
 * depth doesn't fit a single-day migration and would force the
 * JSON-backed fake product handler to grow a bulk-upsert surface that contradicts
 * the CQRS split. Phase 2 will land it as a dedicated Cascade Diamond
 * pattern (`insurance-claim` demo). The ALPS id remains documented;
 * no Be Input or BEAR resource is shipped for it yet.
 *
 * Failure mapping:
 *   - UnauthorizedAdminAccessException → 403
 *
 * Success: 200 with the CSV as the response body's `csv` field and
 * the row count as `count`. The current first iteration returns the
 * CSV in the JSON body for testability; an HTTP-streaming Phase 2
 * variant will set `Content-Type: text/csv` and stream the bytes
 * directly. The shape here exists so the BEAR + Be integration is
 * proven end-to-end before adding stream plumbing.
 */
class ProductCsv extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new AdminExportProductInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminProductCsvExported);

        $this->code = Code::OK;
        $this->headers['Content-Type'] = 'text/csv; charset=UTF-8';
        $this->headers['Content-Disposition'] = 'attachment; filename="products.csv"';
        $this->body = [
            'csv' => $final->csv,
            'count' => $final->count,
        ];

        return $this;
    }
}
