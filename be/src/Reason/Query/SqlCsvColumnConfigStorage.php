<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use Aura\Sql\ExtendedPdoInterface;
use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;
use Override;
use Throwable;

final class SqlCsvColumnConfigStorage implements CsvColumnConfigStorageInterface
{
    public function __construct(
        private readonly InternalDbQueryInterface $db,
        private readonly ExtendedPdoInterface $connection,
    ) {}

    /** @return list<CsvColumnConfigEntity> sorted by sortNo */
    #[Override]
    public function listByType(int $csvType): array
    {
        return array_map(
            $this->hydrate(...),
            $this->db->csv_column_list_by_type(csvType: $csvType),
        );
    }

    /** @param list<CsvColumnConfigEntity> $entries */
    #[Override]
    public function replaceType(int $csvType, array $entries): void
    {
        $this->withAtomic(function () use ($csvType, $entries): void {
            $this->db->csv_column_delete_by_type(csvType: $csvType);
            foreach ($entries as $entry) {
                $this->db->csv_column_insert(
                    csvType: $entry->csvType,
                    entityName: $entry->columnName,
                    fieldName: $entry->columnName,
                    dispName: $entry->columnName,
                    sortNo: $entry->sortNo,
                    enabled: $entry->enabled ? 1 : 0,
                    discriminator: 'csv',
                );
            }
        });
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): CsvColumnConfigEntity
    {
        return new CsvColumnConfigEntity(
            csvType: (int) $row['csv_type_id'],
            columnName: (string) $row['field_name'],
            enabled: (bool) $row['enabled'],
            sortNo: (int) $row['sort_no'],
        );
    }

    private function withAtomic(callable $work): void
    {
        if ($this->connection->inTransaction()) {
            $this->db->csv_column_savepoint();
            try {
                $work();
                $this->db->csv_column_release_savepoint();
            } catch (Throwable $e) {
                $this->db->csv_column_rollback_savepoint();

                throw $e;
            }

            return;
        }

        $this->connection->beginTransaction();
        try {
            $work();
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }
}
