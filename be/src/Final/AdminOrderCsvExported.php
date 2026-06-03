<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Csv\CsvColumnLayout;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\CsvColumnConfigStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

use function assert;
use function count;
use function fclose;
use function fopen;
use function fputcsv;
use function rewind;
use function stream_get_contents;

/**
 * Admin order CSV exported — Final, the back-office CSV download
 * payload for the full order list (Wave 9η).
 *
 *   AdminExportOrderInput → AdminOrderCsvExported  (Direct, safe read)
 *
 * AUTHZ — admin firewall:
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess (403)
 *
 * Light implementation: aggregate every finalized order via
 * {@see OrderQueryInterface::listAll}, dump as RFC 4180 CSV. The
 * Wave 9η iteration emits the full corpus — search-condition
 * filtering is Phase 2 scope (mirrors the Wave 8 product CSV
 * export decision).
 *
 * Column order (the EC-CUBE admin export's "minimum coherent set"):
 *   orderNo, customerId, orderStatus, orderDate, total, paymentTotal,
 *   subtotal, deliveryFeeTotal, charge, discount, tax
 */
final readonly class AdminOrderCsvExported
{
    /** dtb_csv csv_type_id for the order export. */
    private const CSV_TYPE = 1;

    /** @var list<string> */
    private const DEFAULT_COLUMNS = [
        'orderNo',
        'customerId',
        'orderStatus',
        'orderDate',
        'total',
        'paymentTotal',
        'subtotal',
        'deliveryFeeTotal',
        'charge',
        'discount',
        'tax',
    ];

    public string $csv;
    public int $rowCount;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] CsvColumnConfigStorageInterface $csvColumnConfig,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $layout = CsvColumnLayout::resolve(
            self::DEFAULT_COLUMNS,
            $csvColumnConfig->listByType(self::CSV_TYPE),
        );

        // listAll is capped — for Wave 9η we pull the head of the list.
        // Phase 2 will page through.
        $rows = $orderQuery->list(1000, 0);

        $handle = fopen('php://temp', 'rb+');
        assert($handle !== false);

        fputcsv($handle, $layout->columns, ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, $layout->project($this->encodeRow($row)), ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        assert($csv !== false);
        fclose($handle);

        $this->csv = $csv;
        $this->rowCount = count($rows);
    }

    /**
     * @return array<string, string|int>
     */
    private function encodeRow(FinalizedOrderEntity $row): array
    {
        return [
            'orderNo' => $row->orderNo,
            'customerId' => $row->customerId,
            'orderStatus' => (string) $row->orderStatus,
            'orderDate' => $row->orderDate,
            'total' => (string) $row->total,
            'paymentTotal' => (string) $row->paymentTotal,
            'subtotal' => (string) $row->subtotal,
            'deliveryFeeTotal' => (string) $row->deliveryFeeTotal,
            'charge' => (string) $row->charge,
            'discount' => (string) $row->discount,
            'tax' => (string) $row->tax,
        ];
    }
}
