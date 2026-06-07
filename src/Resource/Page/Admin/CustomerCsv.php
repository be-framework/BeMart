<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerCsvExported;
use MyVendor\BeMart\Be\Input\AdminExportCustomerInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goExportCustomer — 会員CSVをエクスポートする (Wave 9).
 *
 * onGet only — safe download. Admin-only. Mirrors Wave 8α's
 * {@see ProductCsv} and Wave 8β's {@see Category\Csv} pattern.
 *
 * Failure mapping:
 *   - UnauthorizedAdminAccessException → 403
 *
 * Success: 200 with the CSV as the response body's `csv` field and the
 * row count as `rowCount`. The Final emits the RFC 4180 dump via PHP's
 * native fputcsv() (same approach as Wave 8β CategoryCsvExported); the
 * Resource layer sets the `Content-Type: text/csv` and
 * `Content-Disposition: attachment` headers.
 */
class CustomerCsv extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /** ALPS `goExportCustomer` に対応する GET 操作。 */
    #[Alps('goExportCustomer')]
    #[JsonSchema(schema: 'get-admin-customer-csv.json')]
    #[Link(rel: 'goCustomerList', href: 'page://self/admin/customer-list')]
    #[Link(rel: 'goExportClassName', href: 'page://self/admin/class-name/class-name-export')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new AdminExportCustomerInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminCustomerCsvExported);

        $this->code = Code::OK;
        $this->headers['Content-Type'] = 'text/csv; charset=UTF-8';
        $this->headers['Content-Disposition'] = 'attachment; filename="customers.csv"';
        $this->body = [
            'csv' => $final->csv,
            'rowCount' => $final->rowCount,
        ];

        return $this;
    }
}
