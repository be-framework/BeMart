<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * 規格名 / 規格分類 CSV export-import boundary
 * (`goExportClassName` / `goExportClassCategory` /
 * `doImportClassNameCsv` / `doImportClassCategoryCsv`).
 *
 * EC-CUBE renders these masters as EC-CUBE-format CSV downloads and
 * accepts uploads to bulk-create/update them. The CSV encoding, the
 * download headers, and the upload parse/validate/persist pipeline stay
 * behind this boundary; the Be Finals depend only on this interface.
 */
interface ClassCsvCompatibilityInterface
{
    public function exportClassName(): CsvDocument;

    public function exportClassCategory(string|null $classNameId = null): CsvDocument;

    /** @return int rows accepted */
    public function importClassName(string $csv): int;

    /** @return int rows accepted */
    public function importClassCategory(string $csv): int;
}
