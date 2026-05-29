<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface;
use MyVendor\BeMart\Be\Reason\Service\CsvDocument;
use Override;

use function array_filter;
use function array_values;
use function count;
use function explode;
use function max;
use function trim;

/** Deterministic 規格名/規格分類 CSV boundary for tests. */
final class FakeClassCsvCompatibility implements ClassCsvCompatibilityInterface
{
    /** @var list<string> */
    public array $imports = [];

    #[Override]
    public function exportClassName(): CsvDocument
    {
        return new CsvDocument("規格名ID,規格名\r\ncn-color,カラー\r\n", 'class_name.csv', 'attachment; filename="class_name.csv"');
    }

    #[Override]
    public function exportClassCategory(string|null $classNameId = null): CsvDocument
    {
        return new CsvDocument("規格分類ID,規格名ID,規格分類名\r\ncc-red,cn-color,赤\r\n", 'class_category.csv', 'attachment; filename="class_category.csv"');
    }

    #[Override]
    public function importClassName(string $csv): int
    {
        $this->imports[] = $csv;

        return $this->countDataRows($csv);
    }

    #[Override]
    public function importClassCategory(string $csv): int
    {
        $this->imports[] = $csv;

        return $this->countDataRows($csv);
    }

    private function countDataRows(string $csv): int
    {
        $lines = array_values(array_filter(
            explode("\n", trim($csv)),
            static fn (string $line): bool => trim($line) !== '',
        ));

        return max(0, count($lines) - 1);
    }
}
