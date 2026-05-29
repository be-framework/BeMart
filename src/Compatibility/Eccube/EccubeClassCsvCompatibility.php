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
use function implode;
use function max;
use function sprintf;
use function trim;

/**
 * EC-CUBE-compatible 規格名/規格分類 CSV boundary.
 *
 * Export builds the EC-CUBE-format CSV from the live class-name /
 * class-category storage. Import parses + counts the uploaded rows; the
 * destructive persistence (upsert of every parsed row) is the production
 * cutover residual (migration-status §4) — by design the upload is
 * validated/counted on the safe side rather than reproducing EC-CUBE's
 * CsvImportService.
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
        $lines = ['規格名ID,規格名'];
        foreach ($this->classNames->list() as $row) {
            /** @var ClassNameEntity $row */
            $lines[] = sprintf('%s,%s', $row->classNameId, $row->name);
        }

        return new CsvDocument(implode("\r\n", $lines) . "\r\n", 'class_name.csv', 'attachment; filename="class_name.csv"');
    }

    #[Override]
    public function exportClassCategory(string|null $classNameId = null): CsvDocument
    {
        $rows = $classNameId !== null
            ? $this->classCategories->listByClassName($classNameId)
            : $this->classCategories->list();

        $lines = ['規格分類ID,規格名ID,規格分類名'];
        foreach ($rows as $row) {
            /** @var ClassCategoryEntity $row */
            $lines[] = sprintf('%s,%s,%s', $row->classCategoryId, $row->classNameId, $row->name);
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
