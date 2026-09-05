<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Csv;

use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;

use function array_flip;
use function usort;

/**
 * Resolved CSV column layout — the bridge between the stored
 * configuration (doUpdateCsv / dtb_csv) and an export Final's hardcoded
 * default column set (Phase 2, the doUpdateCsv consumption side).
 *
 * Each export Final owns a default column vector (the full corpus in a
 * fixed order). `resolve` overlays the admin's saved configuration on
 * top of that vector:
 *
 *   - no configuration for the csvType → emit every default column in
 *     the default order (Wave 9 behaviour, unchanged).
 *   - a configuration exists           → emit only the `enabled`
 *     columns, in the admin's `sortNo` order, restricted to columns the
 *     Final actually knows how to encode.
 *
 * Two deliberate safety rails:
 *   - Unknown column names in the configuration (a column the Final
 *     does not encode — e.g. a dtb_csv field BeMart has not modeled
 *     yet) are silently dropped rather than emitting an empty cell or
 *     crashing. The export shape stays a subset of the default vector.
 *   - A configuration that enables nothing the Final knows (every
 *     enabled column is unknown, or every known column is disabled)
 *     falls back to the full default vector. A zero-column export is
 *     never what the admin meant, and a stale config that names only
 *     since-dropped columns must not silently produce an empty file.
 *
 * The configuration is sorted by `sortNo` defensively so the layout is
 * correct regardless of whether the caller pre-sorted it (the storage
 * contract sorts, but the value object does not depend on that).
 */
final readonly class CsvColumnLayout
{
    /** @param list<string> $columns ordered selected column names — doubles as the header row */
    private function __construct(public array $columns)
    {
    }

    /**
     * @param list<string>                $defaultColumns full column set, in default order
     * @param list<CsvColumnConfigEntity> $config         stored configuration (may be empty)
     */
    public static function resolve(array $defaultColumns, array $config): self
    {
        if ($config === []) {
            return new self($defaultColumns);
        }

        // Stable sort by sortNo — PHP's usort is stable since 8.0, so
        // ties keep the storage's insertion order (its `id ASC` tail).
        usort(
            $config,
            static fn (CsvColumnConfigEntity $a, CsvColumnConfigEntity $b): int => $a->sortNo <=> $b->sortNo,
        );

        $known = array_flip($defaultColumns);
        $selected = [];
        foreach ($config as $entry) {
            if ($entry->enabled && isset($known[$entry->columnName])) {
                $selected[] = $entry->columnName;
            }
        }

        return new self($selected === [] ? $defaultColumns : $selected);
    }

    /**
     * Project one fully-encoded row (every default column → cell) down
     * to the resolved layout, in layout order, with every cell passed
     * through {@see CsvFormulaGuard} — this is the projection every
     * export Final funnels its rows through, so it is also where CSV
     * formula injection is neutralised.
     *
     * @param array<string, string|int> $cells columnName → cell value, keyed by the default column set
     *
     * @return list<string|int>
     */
    public function project(array $cells): array
    {
        $line = [];
        foreach ($this->columns as $name) {
            // `?? ''` is unreachable in practice — the layout is always
            // a subset of the default columns the caller keys $cells by
            // — but it keeps the projection total for the type checker.
            $line[] = CsvFormulaGuard::neutralize($cells[$name] ?? '');
        }

        return $line;
    }
}
