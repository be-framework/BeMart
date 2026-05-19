<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ColumnsFormatException;

use function count;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function mb_strlen;
use function preg_match;
use function trim;

/**
 * CSV column-vector list — Wave 9 (doUpdateCsv).
 *
 * The admin form posts a list of column entries; each entry must be a
 * 3-tuple of {columnName: string, enabled: bool, sortNo: int}. Validation
 * walks the list and rejects malformed shapes.
 *
 * Shape rules per entry:
 *   - columnName: non-empty, max 64 chars, ASCII identifier characters
 *     only (DB-column-name conservatism; no SQL injection vectors).
 *   - enabled:    bool.
 *   - sortNo:     0..999 (EC-CUBE admin grid never exceeds 3 digits).
 *
 * The list itself must contain 1..100 entries (one CSV export category
 * tops out well under 100 columns in EC-CUBE).
 *
 * @link https://schema.org/ItemList
 */
final class Columns
{
    /** @param array<int, mixed> $columns */
    #[Validate]
    public function validate(array $columns): void
    {
        $count = count($columns);
        if ($count < 1 || $count > 100) {
            throw new ColumnsFormatException();
        }

        foreach ($columns as $entry) {
            if (! is_array($entry)) {
                throw new ColumnsFormatException();
            }

            if (! isset($entry['columnName'], $entry['enabled'], $entry['sortNo'])) {
                throw new ColumnsFormatException();
            }

            $name = $entry['columnName'];
            if (! is_string($name) || trim($name) === '' || mb_strlen($name) > 64) {
                throw new ColumnsFormatException();
            }

            if (! preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                throw new ColumnsFormatException();
            }

            if (! is_bool($entry['enabled'])) {
                throw new ColumnsFormatException();
            }

            $sortNo = $entry['sortNo'];
            if (! is_int($sortNo) || $sortNo < 0 || $sortNo > 999) {
                throw new ColumnsFormatException();
            }
        }
    }
}
