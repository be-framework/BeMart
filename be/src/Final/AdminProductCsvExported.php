<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
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
 *   AdminSessionInterface::adminId() === null → UnauthorizedAdminAccess
 *
 * Public surface: `csv` (UTF-8 string with header row + one row per
 * product), `count` (number of data rows). The resource layer wraps
 * the CSV string in an HTTP response with `Content-Type: text/csv;
 * charset=UTF-8` and `Content-Disposition: attachment` headers — the
 * Final itself only assembles the bytes so it stays testable without
 * an HTTP context.
 *
 * Column order (matches the import shape stub when it lands):
 *   productCode, productName, price02, stock, productStatus,
 *   description, searchWord, note, sortNo
 *
 * `null` is emitted as an empty cell (EC-CUBE convention).
 */
final readonly class AdminProductCsvExported
{
    public string $csv;
    public int $count;

    public function __construct(
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] ProductQueryInterface $productQuery,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $productQuery->listForExport();

        $handle = fopen('php://memory', 'w+');
        if ($handle === false) {
            // php://memory is process-local and not subject to disk
            // quota — this branch only fires under extreme memory
            // pressure, but the explicit guard keeps Psalm happy.
            $this->csv = '';
            $this->count = 0;

            return;
        }

        fputcsv($handle, [
            'productCode',
            'productName',
            'price02',
            'stock',
            'productStatus',
            'description',
            'searchWord',
            'note',
            'sortNo',
        ], ',', '"', '\\');

        foreach ($rows as $row) {
            fputcsv($handle, $this->encodeRow($row), ',', '"', '\\');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $this->csv = $csv === false ? '' : $csv;
        $this->count = count($rows);
    }

    /**
     * @return list<string|int>
     */
    private function encodeRow(ProductEntity $row): array
    {
        return [
            $row->productCode,
            $row->productName,
            $row->price02,
            $row->stock ?? '',
            $row->productStatus,
            $row->description ?? '',
            $row->searchWord ?? '',
            $row->note ?? '',
            $row->sortNo ?? '',
        ];
    }
}
