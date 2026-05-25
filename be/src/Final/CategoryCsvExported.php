<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;

use function fclose;
use function fopen;
use function fputcsv;
use function rewind;
use function stream_get_contents;

/**
 * Category CSV exported — Final, the back-office CSV download
 * payload (Wave 7).
 *
 *   ExportCategoryInput → CategoryCsvExported (Direct, safe read)
 *
 * AUTHZ: refuses non-admin requests via
 * {@see UnauthorizedAdminAccessException} (403).
 *
 * Format is RFC 4180 — header row + one row per category. Encoded
 * via PHP's native fputcsv() so quoting / escaping is identical to
 * what EC-CUBE downstream tooling would expect.
 */
final readonly class CategoryCsvExported
{
    public string $csv;
    public int $rowCount;

    public function __construct(
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] CategoryStorageInterface $categories,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $categories->list();

        $handle = fopen('php://temp', 'rb+');
        // Safe-list initialisation: php://temp open never returns false in
        // practice; the cast removes the false-branch from the type so
        // fputcsv receives a `resource`.
        \assert($handle !== false);

        // PHP 8.4 deprecates the implicit $escape default; pass '' so
        // RFC 4180 quoting remains stable across versions.
        fputcsv($handle, ['categoryId', 'categoryName', 'parentId', 'sortNo'], ',', '"', '');
        foreach ($rows as $row) {
            \assert($row instanceof CategoryEntity);
            fputcsv($handle, [
                $row->categoryId,
                $row->categoryName,
                $row->parentId ?? '',
                (string) $row->sortNo,
            ], ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        \assert($csv !== false);
        fclose($handle);

        $this->csv = $csv;
        $this->rowCount = \count($rows);
    }
}
