<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;
use Override;

use function usort;

/**
 * In-memory CSV column configuration store (Wave 9). Singleton-bound
 * so a doUpdateCsv POST's write is visible to a subsequent read in the
 * same request / test.
 *
 * Starts empty — Wave 9 first iteration ships the persistence contract,
 * not a seed of every dtb_csv row. Tests POST their own configuration
 * vectors and verify the round trip.
 */
final class FakeCsvColumnConfigStorage implements CsvColumnConfigStorageInterface
{
    /** @var array<int, list<CsvColumnConfigEntity>> keyed by csvType */
    private array $byType = [];

    /** @return list<CsvColumnConfigEntity> */
    #[Override]
    public function listByType(int $csvType): array
    {
        $rows = $this->byType[$csvType] ?? [];
        usort($rows, static fn (CsvColumnConfigEntity $a, CsvColumnConfigEntity $b): int => $a->sortNo <=> $b->sortNo);

        return $rows;
    }

    /** @param list<CsvColumnConfigEntity> $entries */
    #[Override]
    public function replaceType(int $csvType, array $entries): void
    {
        $this->byType[$csvType] = $entries;
    }
}
