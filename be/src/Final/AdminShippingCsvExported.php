<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Csv\CsvColumnLayout;
use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use MyVendor\BeMart\Be\Reason\Query\CsvColumnConfigStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
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
 * Admin shipping CSV exported — Final, the back-office CSV download
 * payload for the shipping list (Wave 9η).
 *
 *   AdminExportShippingInput → AdminShippingCsvExported (Direct, safe read)
 *
 * AUTHZ — admin firewall:
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess (403)
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
    /** dtb_csv csv_type_id for the shipping export. */
    private const CSV_TYPE = 4;

    /** @var list<string> */
    private const DEFAULT_COLUMNS = [
        'orderNo',
        'name01',
        'name02',
        'postalCode',
        'pref',
        'addr01',
        'addr02',
        'phoneNumber',
        'trackingNumber',
    ];

    public string $csv;
    public int $rowCount;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] ShippingAddressStorageInterface $shippingAddresses,
        #[Inject] CsvColumnConfigStorageInterface $csvColumnConfig,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $layout = CsvColumnLayout::resolve(
            self::DEFAULT_COLUMNS,
            $csvColumnConfig->listByType(self::CSV_TYPE),
        );
        $rows = $shippingAddresses->list();

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
    private function encodeRow(ShippingAddressEntity $row): array
    {
        return [
            'orderNo' => $row->orderNo,
            'name01' => $row->name01,
            'name02' => $row->name02,
            'postalCode' => $row->postalCode,
            'pref' => (string) $row->pref,
            'addr01' => $row->addr01,
            'addr02' => $row->addr02,
            'phoneNumber' => $row->phoneNumber,
            // trackingNumber is exposed empty for this iteration — see
            // the class docblock; the column stays in the shape so the
            // export → fill → import round-trip is stable.
            'trackingNumber' => '',
        ];
    }
}
