<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CsvConfigUpdated;

/**
 * Input for doUpdateCsv — admin updates the CSV column configuration
 * for one csvType (Wave 9).
 *
 *   UpdateCsvInput → CsvConfigUpdated  (Direct, idempotent)
 *
 * The EC-CUBE admin form posts the whole column vector for ONE csvType
 * at a time (order=1, customer=2, product=3, shipping=4); the storage
 * does a per-type atomic replace so the table cannot drift into a
 * mixed-old-and-new row set.
 *
 * AUTHZ in the Final (AdminSession).
 *
 * Wave 9 first iteration scope:
 *   - persists the configuration round-trip (the storage holds it,
 *     a subsequent read sees the write)
 *   - the export Finals (Wave 8α product, Wave 8β category, Wave 9
 *     customer) still emit the hardcoded column list — consuming this
 *     configuration is Phase 2.
 *
 * The columns list arrives as a list of associative arrays:
 *   [{columnName: string, enabled: bool, sortNo: int}, ...]
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(CsvConfigUpdated::class)]
final readonly class UpdateCsvInput
{
    /**
     * @param list<array{columnName: string, enabled: bool, sortNo: int}> $columns
     *
     * @psalm-taint-source input $csvType
     * @psalm-taint-source input $columns
     */
    public function __construct(
        public int $csvType,
        public array $columns,
    ) {
    }
}
