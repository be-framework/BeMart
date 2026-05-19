<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
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
 *   AdminSessionInterface::adminId() === null → UnauthorizedAdminAccess (403)
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
    public string $csv;
    public int $rowCount;

    public function __construct(
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        // listAll is capped — for Wave 9η we pull the head of the list.
        // Phase 2 will page through.
        $rows = $orderQuery->listAll(1000, 0);

        $handle = fopen('php://temp', 'rb+');
        assert($handle !== false);

        fputcsv($handle, [
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
        ], ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->orderNo,
                $row->customerId,
                (string) $row->orderStatus,
                $row->orderDate,
                (string) $row->total,
                (string) $row->paymentTotal,
                (string) $row->subtotal,
                (string) $row->deliveryFeeTotal,
                (string) $row->charge,
                (string) $row->discount,
                (string) $row->tax,
            ], ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        assert($csv !== false);
        fclose($handle);

        $this->csv = $csv;
        $this->rowCount = count($rows);
    }
}
