<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Csv\CsvFormulaGuard;
use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use MyVendor\BeMart\Be\Reason\Provider\ClassCategoryIdProvider;
use MyVendor\BeMart\Be\Reason\Provider\ClassNameIdProvider;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\CsvDocument;
use Override;

use function array_shift;
use function ctype_digit;
use function explode;
use function implode;
use function preg_match;
use function str_getcsv;
use function str_replace;
use function trim;

/**
 * EC-CUBE-compatible 規格名/規格分類 CSV boundary.
 *
 * Export builds the EC-CUBE-format CSV from the live class-name /
 * class-category storage, quoting every field per RFC 4180 so a value
 * containing a comma, double-quote or newline is enclosed instead of
 * corrupting the column layout — the same correctness the sibling exporters
 * get from {@see fputcsv} ({@see \MyVendor\BeMart\Be\Final\AdminProductCsvExported}
 * et al.), done here with a pure encoder so there is no per-row stream. Import
 * parses EC-CUBE-style rows and upserts them through the same storage boundary
 * as the admin form flows, so browser uploads can be read back from the master
 * list screens.
 */
final class EccubeClassCsvCompatibility implements ClassCsvCompatibilityInterface
{
    public function __construct(
        private readonly ClassNameStorageInterface $classNames,
        private readonly ClassCategoryStorageInterface $classCategories,
        private readonly ClassNameIdProvider $classNameIds,
        private readonly ClassCategoryIdProvider $classCategoryIds,
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
        $imported = 0;
        foreach ($this->dataRows($csv) as $row) {
            $id = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));
            if ($name === '') {
                continue;
            }

            $classNameId = $id === '' ? $this->classNameIds->get() : $id;
            if (! ctype_digit($classNameId)) {
                continue;
            }

            $this->classNames->put(new ClassNameEntity(
                classNameId: $classNameId,
                name: $name,
            ));
            $imported++;
        }

        return $imported;
    }

    #[Override]
    public function importClassCategory(string $csv): int
    {
        $imported = 0;
        foreach ($this->dataRows($csv) as $row) {
            $id = trim((string) ($row[0] ?? ''));
            $classNameId = trim((string) ($row[1] ?? ''));
            $name = trim((string) ($row[2] ?? ''));
            if ($classNameId === '' || $name === '' || ! ctype_digit($classNameId)) {
                continue;
            }

            $classCategoryId = $id === '' ? $this->classCategoryIds->get() : $id;
            if (! ctype_digit($classCategoryId)) {
                continue;
            }

            $this->classCategories->put(new ClassCategoryEntity(
                classCategoryId: $classCategoryId,
                classNameId: $classNameId,
                name: $name,
            ));
            $imported++;
        }

        return $imported;
    }

    /**
     * Encode one CSV record with RFC-4180 quoting (no record terminator;
     * record separation is the caller's "\r\n" join — EC-CUBE emits
     * CRLF-delimited rows).
     *
     * @param list<string> $fields
     */
    private function encodeRow(array $fields): string
    {
        $quoted = [];
        foreach ($fields as $field) {
            $quoted[] = $this->quoteField($field);
        }

        return implode(',', $quoted);
    }

    /**
     * Neutralise CSV formula injection ({@see CsvFormulaGuard} — the same
     * rule {@see \MyVendor\BeMart\Be\Reason\Csv\CsvColumnLayout::project()}
     * applies to the layout-driven exports), then RFC-4180 quote: only
     * when the field contains the delimiter, the enclosure, CR or LF, wrap
     * it in double-quotes and double any embedded double-quote.
     * Byte-identical to fputcsv(escape: '') for these inputs, but pure — no
     * per-row php://memory stream and so no malformed-output fallback path.
     */
    private function quoteField(string $field): string
    {
        $guarded = (string) CsvFormulaGuard::neutralize($field);

        if (preg_match('/[",\r\n]/', $guarded) === 1) {
            return '"' . str_replace('"', '""', $guarded) . '"';
        }

        return $guarded;
    }

    /**
     * @return list<list<string|null>>
     */
    private function dataRows(string $csv): array
    {
        $trimmed = trim($csv);
        if ($trimmed === '') {
            return [];
        }

        $lines = explode("\n", $trimmed);
        array_shift($lines);

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $rows[] = str_getcsv($line, ',', '"', '\\');
        }

        return $rows;
    }
}
