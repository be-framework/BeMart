<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\CsvDocument;
use Override;

use function array_filter;
use function array_values;
use function count;
use function explode;
use function fclose;
use function fopen;
use function fputcsv;
use function implode;
use function max;
use function rewind;
use function rtrim;
use function stream_get_contents;
use function trim;

/**
 * EC-CUBE-compatible 規格名/規格分類 CSV boundary.
 *
 * Export builds the EC-CUBE-format CSV from the live class-name /
 * class-category storage, encoding every field through {@see fputcsv} so a
 * value containing a comma, double-quote or newline is quoted (RFC 4180)
 * instead of corrupting the column layout — matching the sibling exporters
 * ({@see \MyVendor\BeMart\Be\Final\AdminProductCsvExported} et al.). Import
 * parses + counts the uploaded rows; the destructive persistence (upsert of
 * every parsed row) is the production cutover residual (migration-status §4)
 * — by design the upload is validated/counted on the safe side rather than
 * reproducing EC-CUBE's CsvImportService.
 */
final class EccubeClassCsvCompatibility implements ClassCsvCompatibilityInterface
{
    public function __construct(
        private readonly ClassNameStorageInterface $classNames,
        private readonly ClassCategoryStorageInterface $classCategories,
    ) {
    }

    #[Override]
    public function exportClassName(): CsvDocument
    {
        $lines = [$this->encodeRow(['規格名ID', '規格名'])];
        foreach ($this->classNames->list() as $row) {
            /** @var ClassNameEntity $row */
            $lines[] = $this->encodeRow([$row->classNameId, $row->name]);
        }

        return new CsvDocument(implode("\r\n", $lines) . "\r\n", 'class_name.csv', 'attachment; filename="class_name.csv"');
    }

    #[Override]
    public function exportClassCategory(string|null $classNameId = null): CsvDocument
    {
        $rows = $classNameId !== null
            ? $this->classCategories->listByClassName($classNameId)
            : $this->classCategories->list();

        $lines = [$this->encodeRow(['規格分類ID', '規格名ID', '規格分類名'])];
        foreach ($rows as $row) {
            /** @var ClassCategoryEntity $row */
            $lines[] = $this->encodeRow([$row->classCategoryId, $row->classNameId, $row->name]);
        }

        return new CsvDocument(implode("\r\n", $lines) . "\r\n", 'class_category.csv', 'attachment; filename="class_category.csv"');
    }

    #[Override]
    public function importClassName(string $csv): int
    {
        return $this->countDataRows($csv);
    }

    #[Override]
    public function importClassCategory(string $csv): int
    {
        return $this->countDataRows($csv);
    }

    /**
     * Encode one CSV record with RFC-4180 quoting (no record terminator).
     *
     * @param list<string> $fields
     */
    private function encodeRow(array $fields): string
    {
        $handle = fopen('php://memory', 'r+');
        if ($handle === false) {
            // php://memory only fails under extreme memory pressure.
            return implode(',', $fields);
        }

        // escape: '' disables PHP's legacy backslash escaping (deprecated in
        // 8.4) so embedded quotes are doubled per RFC 4180.
        fputcsv($handle, $fields, ',', '"', '');
        rewind($handle);
        $line = stream_get_contents($handle);
        fclose($handle);

        // Drop fputcsv's trailing "\n"; record separation is the caller's
        // "\r\n" join (EC-CUBE emits CRLF-delimited rows).
        return rtrim($line === false ? '' : $line, "\n");
    }

    private function countDataRows(string $csv): int
    {
        $lines = array_values(array_filter(
            explode("\n", trim($csv)),
            static fn (string $line): bool => trim($line) !== '',
        ));

        // Every non-empty line except the header row is a data row.
        return max(0, count($lines) - 1);
    }
}
