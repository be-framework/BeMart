<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_shift;
use function count;
use function explode;
use function str_getcsv;
use function trim;

/**
 * Admin shipping CSV imported — Final, real tracking-number ingestion
 * (doImportShippingCsv).
 *
 *   AdminImportShippingCsvInput → AdminShippingCsvImported
 *                                  (Direct, unsafe, admin AUTHZ)
 *
 * EC-CUBE's shipping CSV bulk-sets the お問い合わせ番号 (tracking number) on
 * existing shipping rows. Columns: `受注番号, お問い合わせ番号`. Each row is
 * resolved against {@see OrderQueryInterface::byOrderNo}; a known order
 * has its tracking number written via the same narrow
 * {@see ShippingAddressStorageInterface::updateTrackingNumber} surface
 * the inline `doUpdateTrackingNumber` transition uses — only the
 * `tracking_number` column is touched. Rows whose order is unknown are
 * counted as `skipped` rather than failing the whole import (EC-CUBE's
 * per-row tolerance).
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). The
 * header row is dropped. Durable persistence is exercised by the SQL
 * suite (Fake writes are no-ops, the established convention).
 */
final readonly class AdminShippingCsvImported
{
    public bool $accepted;

    /** Total non-empty lines INCLUDING the header row; data rows = lineCount - 1. */
    public int $lineCount;

    public int $imported;
    public int $skipped;
    public string $message;

    public function __construct(
        #[Input] string $csv,
        #[Inject] AdminSession $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] ShippingAddressStorageInterface $shippingAddresses,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $trimmed = trim($csv);
        $lines = $trimmed === '' ? [] : explode("\n", $trimmed);
        $this->lineCount = count($lines);

        // Drop the header row.
        array_shift($lines);

        $imported = 0;
        $skipped = 0;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $fields = str_getcsv($line, ',', '"', '\\');
            $orderNo = trim((string) ($fields[0] ?? ''));
            $trackingNumber = trim((string) ($fields[1] ?? ''));

            if ($orderNo === '') {
                continue;
            }

            $order = $orderQuery->byOrderNo($orderNo);
            if ($order === null) {
                $skipped++;

                continue;
            }

            $shippingAddresses->updateTrackingNumber($order->orderNo, $trackingNumber);
            $imported++;
        }

        $this->imported = $imported;
        $this->skipped = $skipped;
        $this->accepted = true;
        $this->message = "配送CSVを取り込みました（更新 {$imported}件・対象外 {$skipped}件）。";
    }
}
