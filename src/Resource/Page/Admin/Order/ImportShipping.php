<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminShippingCsvImported;
use MyVendor\BeMart\Be\Input\AdminImportShippingCsvInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doImportShippingCsv — 配送CSVをインポートする (Wave 9η,
 * **Phase 2 stub**).
 *
 *   POST /admin/order/import-shipping
 *
 * Mirrors the Wave 8 {@see \MyVendor\BeMart\Resource\Page\Admin\Category\Csv::onPost}
 * stub — accepts the CSV body as a plain string, returns 202 +
 * `accepted=false` with a notice so callers cannot mistake the stub
 * for a real import. The full parser (tracking-number column,
 * shipDate parsing, dry-run preview) is Phase 2.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 */
class ImportShipping extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly AdminSession $adminSession,
    ) {
    }

    /**
     * EC-CUBE 出荷CSV登録 — Order Tier-2.
     *
     * Thin GET renderer for `admin/Order/csv_shipping.twig` — the
     * shipping-CSV upload form. The POST below accepts the uploaded
     * CSV; this GET serves the upload-form shell. AUTHZ is a direct
     * admin-session check (Pattern B — no Be transition is invoked on
     * the GET path); a non-admin firewall is refused with 403.
     */
    #[Alps('doImportShippingCsv')]
    #[JsonSchema(schema: 'get-admin-order-import-shipping.json')]
    #[Link(rel: 'doImportShippingCsv', href: 'page://self/admin/order/import-shipping', method: 'post')]
    #[Link(rel: 'goExportShipping', href: 'page://self/admin/order/export-shipping', method: 'get')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [];

        return $this;
    }

    /**
     * ALPS `doImportShippingCsv` に対応する POST 操作。
     * @psalm-taint-source input $csv
     */
    #[Alps('doImportShippingCsv')]
    #[JsonSchema(schema: 'post-admin-order-import-shipping.json', params: 'post-admin-order-import-shipping.param.json')]
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    #[Link(rel: 'goExportShipping', href: 'page://self/admin/order/export-shipping', method: 'get')]
    #[Link(rel: 'goExportCustomer', href: 'page://self/admin/customer-csv')]
    #[CsrfProtected]
    public function onPost(string $csv): static
    {
        $final = ($this->becoming)(new AdminImportShippingCsvInput(csv: $csv));

        assert($final instanceof AdminShippingCsvImported);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin/order';
        $this->body = [
            'transitionId' => 'doImportShippingCsv',
            'accepted' => $final->accepted,
            'lineCount' => $final->lineCount,
            'imported' => $final->imported,
            'skipped' => $final->skipped,
            'message' => $final->message,
        ];

        return $this;
    }
}
