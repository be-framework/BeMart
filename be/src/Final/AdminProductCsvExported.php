<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Csv\CsvColumnLayout;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\CsvColumnConfigStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

use function count;
use function fclose;
use function fopen;
use function fputcsv;
use function rewind;
use function stream_get_contents;

/**
 * Admin product CSV exported — Final, admin downloads the full product
 * master as a CSV string.
 *
 *   AdminExportProductInput → AdminProductCsvExported  (Direct, safe read)
 *
 * ALPS doc verbatim: "商品マスタをCSV形式でダウンロードする。検索条件で
 * 絞り込み可能。" The Wave 8 first iteration emits the full corpus —
 * the search-condition filter is deferred to Phase 2 (the admin grid
 * already exposes the filter via goProductList; pushing it into the
 * export adds a second filter surface).
 *
 * AUTHZ — admin firewall:
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess
 *
 * Public surface: `csv` (UTF-8 string with header row + one row per
 * product), `count` (number of data rows). The resource layer wraps
 * the CSV string in an HTTP response with `Content-Type: text/csv;
 * charset=UTF-8` and `Content-Disposition: attachment` headers — the
 * Final itself only assembles the bytes so it stays testable without
 * an HTTP context.
 *
 * Default column order (matches the import shape stub when it lands):
 *   productCode, productName, price02, stock, productStatus,
 *   description, searchWord, note
 *
 * The admin's saved doUpdateCsv configuration (dtb_csv, csvType=3)
 * narrows / reorders this set via {@see CsvColumnLayout}; with no saved
 * configuration the full default vector is emitted (Wave 9 behaviour).
 *
 * `null` is emitted as an empty cell (EC-CUBE convention).
 */
final readonly class AdminProductCsvExported
{
    /** dtb_csv csv_type_id for the product export. */
    private const CSV_TYPE = 3;

    /** Number of products fetched per SQL query during export. */
    private const EXPORT_BATCH_SIZE = 100;

    /** @var list<string> */
    private const DEFAULT_COLUMNS = [
        'productCode',
        'productName',
        'price02',
        'stock',
        'productStatus',
        'description',
        'searchWord',
        'note',
    ];

    public string $csv;
    public int $count;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] ProductQueryInterface $productQuery,
        #[Inject] CsvColumnConfigStorageInterface $csvColumnConfig,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $layout = CsvColumnLayout::resolve(
            self::DEFAULT_COLUMNS,
            $csvColumnConfig->listByType(self::CSV_TYPE),
        );
        $handle = fopen('php://memory', 'w+');
        if ($handle === false) {
            // php://memory is process-local and not subject to disk
            // quota — this branch only fires under extreme memory
            // pressure, but the explicit guard keeps Psalm happy.
            $this->csv = '';
            $this->count = 0;

            return;
        }

        fputcsv($handle, $layout->columns, ',', '"', '\\');

        $count = 0;
        for ($offset = 0; ; $offset += self::EXPORT_BATCH_SIZE) {
            $rows = $productQuery->listForExport(self::EXPORT_BATCH_SIZE, $offset);
            foreach ($rows as $row) {
                fputcsv($handle, $layout->project($this->encodeRow($row)), ',', '"', '\\');
            }

            $count += count($rows);
            if (count($rows) < self::EXPORT_BATCH_SIZE) {
                break;
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $this->csv = $csv === false ? '' : $csv;
        $this->count = $count;
    }

    /**
     * @return array<string, string|int>
     */
    private function encodeRow(ProductEntity $row): array
    {
        return [
            'productCode' => $row->productCode,
            'productName' => $row->productName,
            'price02' => $row->price02,
            'stock' => $row->stock ?? '',
            'productStatus' => $row->productStatus,
            'description' => $row->description ?? '',
            'searchWord' => $row->searchWord ?? '',
            'note' => $row->note ?? '',
        ];
    }
}
