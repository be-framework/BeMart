<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
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
 * Admin shipping CSV exported — Final, the back-office CSV download
 * payload for the shipping list (Wave 9η).
 *
 *   AdminExportShippingInput → AdminShippingCsvExported (Direct, safe read)
 *
 * AUTHZ — admin firewall:
 *   AdminSessionInterface::adminId() === null → UnauthorizedAdminAccess (403)
 *
 * Format: RFC 4180. One header row + one row per recorded shipping
 * address. `trackingNumber` column is exposed empty for the Wave 9η
 * iteration — Phase 2 will materialise it once
 * {@see \MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity} grows
 * the field. The empty column is intentional: it keeps the import
 * shape stable across the export → fill → import workflow.
 */
final readonly class AdminShippingCsvExported
{
    public string $csv;
    public int $rowCount;

    public function __construct(
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] ShippingAddressStorageInterface $shippingAddresses,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $shippingAddresses->list();

        $handle = fopen('php://temp', 'rb+');
        assert($handle !== false);

        fputcsv($handle, [
            'orderNo',
            'name01',
            'name02',
            'postalCode',
            'pref',
            'addr01',
            'addr02',
            'phoneNumber',
            'trackingNumber',
        ], ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->orderNo,
                $row->name01,
                $row->name02,
                $row->postalCode,
                (string) $row->pref,
                $row->addr01,
                $row->addr02,
                $row->phoneNumber,
                '',
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
