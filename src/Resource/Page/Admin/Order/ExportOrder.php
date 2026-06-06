<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderCsvExported;
use MyVendor\BeMart\Be\Input\AdminExportOrderInput;

use function assert;

/**
 * EC-CUBE goExportOrder — 受注CSVをエクスポートする (Wave 9η).
 *
 *   GET /admin/order/export-order
 *
 * Light implementation — dumps every finalized order via
 * {@see \MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface::listAll}.
 * Search-condition filtering is Phase 2 (mirrors the Wave 8
 * AdminProductCsv decision).
 *
 * Failure mapping:
 *   - UnauthorizedAdminAccessException → 403 (no admin session)
 */
class ExportOrder extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    #[Link(rel: 'goExportShipping', href: 'page://self/admin/order/export-shipping', method: 'get')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new AdminExportOrderInput());

        assert($final instanceof AdminOrderCsvExported);

        $this->code = Code::OK;
        $this->headers['Content-Type'] = 'text/csv; charset=UTF-8';
        $this->body = [
            'csv' => $final->csv,
            'rowCount' => $final->rowCount,
        ];

        return $this;
    }
}
