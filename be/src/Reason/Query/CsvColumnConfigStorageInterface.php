<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;
use MyVendor\BeMart\Be\Reason\Query\Param\CsvColumnConfigList;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * CSV column configuration storage — unified Query + Command (Wave 9).
 *
 *   - listByType(int $csvType)                     → every column row for a type
 *   - deleteType(int $csvType)                     → clear the old vector for a type
 *   - insertType(int $csvType, list $entries)      → insert the new vector for a type
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
    #[DbQuery('csv_column_list_by_type')]
    public function listByType(int $csvType): array;

    #[DbQuery('csv_column_delete_type')]
    public function deleteType(int $csvType): void;

    #[DbQuery('csv_column_insert_type')]
    public function insertType(int $csvType, CsvColumnConfigList $entries): void;

    #[DbQuery('csv_column_replace_type')]
    public function replaceType(int $csvType, CsvColumnConfigList $entries): void;
}
