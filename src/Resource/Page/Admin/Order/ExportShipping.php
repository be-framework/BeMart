<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminShippingCsvExported;
use MyVendor\BeMart\Be\Input\AdminExportShippingInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goExportShipping — 配送CSVをエクスポートする (Wave 9η).
 *
 *   GET /admin/order/export-shipping
 *
 * Pairs with {@see ImportShipping} — the admin workflow is
 * "download → fill tracking numbers offline → upload back". Wave 9η
 * surfaces the export half real, the import half stub (parser is
 * Phase 2).
 *
 * Failure mapping:
 *   - UnauthorizedAdminAccessException → 403 (no admin session)
 */
class ExportShipping extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /** ALPS `goExportShipping` に対応する GET 操作。 */
    #[Alps('goExportShipping')]
    #[JsonSchema(schema: 'get-admin-order-export-shipping.json')]
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    #[Link(rel: 'doImportShippingCsv', href: 'page://self/admin/order/import-shipping', method: 'post')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new AdminExportShippingInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminShippingCsvExported);

        $this->code = Code::OK;
        $this->headers['Content-Type'] = 'text/csv; charset=UTF-8';
        $this->body = [
            'csv' => $final->csv,
            'rowCount' => $final->rowCount,
        ];

        return $this;
    }
}
