<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;

/**
 * CSV column configuration storage — unified Query + Command (Wave 9).
 *
 *   - listByType(int $csvType)                     → every column row for a type
 *   - replaceType(int $csvType, list $entries)     → atomic per-type vector replace
 *
 * The EC-CUBE admin form posts the entire column vector for one csvType
 * at once; modeled as `replaceType` so the storage cannot drift into a
 * mixed old / new row set. Phase 2 will surface a column catalog
 * (`listAvailable(csvType)`) and consume the configuration in the
 * export Finals — Wave 9 stores the configuration but the exporters
 * still emit the hardcoded column list (the configuration round-trip
 * is exercised end-to-end; the consumption side is Phase 2).
 */
interface CsvColumnConfigStorageInterface
{
    /** @return list<CsvColumnConfigEntity> sorted by sortNo */
    public function listByType(int $csvType): array;

    /** @param list<CsvColumnConfigEntity> $entries */
    public function replaceType(int $csvType, array $entries): void;
}
