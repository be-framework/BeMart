<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminShippingCsvImported;
use MyVendor\BeMart\Be\Input\AdminImportShippingCsvInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $csv
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    #[Link(rel: 'goExportShipping', href: 'page://self/admin/order/export-shipping', method: 'get')]
    public function onPost(string $csv, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminImportShippingCsvInput(csv: $csv));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminShippingCsvImported);

        $this->code = Code::ACCEPTED;
        $this->body = [
            'accepted' => $final->accepted,
            'lineCount' => $final->lineCount,
            'message' => $final->message,
        ];

        return $this;
    }
}
