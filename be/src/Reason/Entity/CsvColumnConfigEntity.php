<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * CSV column configuration entity — projection of EC-CUBE 4.3 dtb_csv,
 * which records per-column "include in export?" + sort order for each
 * CSV export type (order / product / customer / shipping).
 *
 * One row per (csvType, columnName) pair: `enabled` toggles whether the
 * column is emitted, `sortNo` controls the column order in the export.
 * Wave 9 first iteration models this as a single-shot configuration
 * write (doUpdateCsv) — the EC-CUBE admin form posts the whole column
 * vector for one csvType at once, so the storage replaces the per-type
 * row set atomically.
 *
 * Phase 2 will:
 *   - actually consume this configuration in
 *     {@see AdminProductCsvExported} / {@see AdminCustomerCsvExported}
 *     (Wave 9 emits the hardcoded column list)
 *   - flesh out the column catalog (the available column names per
 *     csvType — Wave 9 just stores whatever the admin POSTs).
 */
final readonly class CsvColumnConfigEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public int $csvType,
        public string $columnName,
        public bool $enabled,
        public int $sortNo,
    ) {
    }
}
