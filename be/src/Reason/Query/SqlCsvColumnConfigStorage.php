<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;
use Override;
use PDO;
use Throwable;

/**
 * Real PDO-backed CSV column configuration storage — Phase 2b.
 *
 * Mirrors {@see FakeCsvColumnConfigStorage} against the live EC-CUBE 4.3
 * schema (`dtb_csv`). Pure prepared statements: no Doctrine, no ORM.
 *
 * `dtb_csv` row model
 * -------------------
 * Each `dtb_csv` row is ONE column-config entry; a csvType (order=1,
 * customer=2, product=3, shipping=4 — `csv_type_id` FK → `mtb_csv_type`)
 * owns many rows. The BeMart {@see CsvColumnConfigEntity} projects four
 * fields off the row:
 *
 *   - `csvType`    ← `csv_type_id`     (smallint unsigned, the FK)
 *   - `columnName` ← `field_name`      (varchar — the EC-CUBE export
 *                                       field; the admin form's per-row
 *                                       handle)
 *   - `enabled`    ← `enabled`         (tinyint(1) → bool)
 *   - `sortNo`     ← `sort_no`         (smallint unsigned → int)
 *
 * The remaining NOT NULL columns EC-CUBE carries — `entity_name`,
 * `disp_name`, `discriminator_type` — are not in the Wave 9 Entity. The
 * INSERT defaults them: `entity_name` / `disp_name` echo the
 * `field_name` (Wave 9 stores whatever the admin POSTs; the column
 * catalog that would supply the real entity/display names is Phase 2),
 * `discriminator_type` = 'csv' (the Doctrine single-table inheritance
 * value EC-CUBE writes). `reference_field_name` and `creator_id` are
 * nullable and written NULL.
 *
 * `csv_type_id` FK → `mtb_csv_type`
 * ---------------------------------
 * `mtb_csv_type` is empty in the structure-only schema dump and
 * `dtb_csv.csv_type_id` carries an enforced FK (FK_F55F48C3E8507796).
 * Because `csvType` must round-trip, the SQL test seeds `mtb_csv_type`
 * with the EC-CUBE 4.3 canonical rows via {@see SqlFixturesTrait::seedCsvTypes}
 * — the same empty-master FK seed precedent {@see SqlFixturesTrait::seedAdminMasters}
 * / {@see SqlFixturesTrait::seedSaleTypes} established.
 *
 * Atomic per-type vector replace
 * ------------------------------
 * The EC-CUBE admin form posts the entire column vector for one csvType
 * at once. {@see replaceType} models that as DELETE-all-rows-for-the-
 * csvType then INSERT-the-new-vector, inside a transaction — the storage
 * cannot drift into a mixed old / new row set. Other csvTypes' rows are
 * untouched: the DELETE is scoped `WHERE csv_type_id = :csv_type`.
 *
 * Transaction strategy — savepoint when nested
 * --------------------------------------------
 * The test base class wraps every test in a transaction it rolls back
 * during tearDown. MySQL/MariaDB do not truly nest transactions: a
 * second `BEGIN` silently commits the outer one. To keep the test-time
 * isolation contract intact while still giving production callers an
 * atomic replace, {@see withAtomic} uses a SAVEPOINT when
 * `inTransaction()` is already true and a full BEGIN/COMMIT otherwise —
 * the same shape {@see SqlCartCommand::withAtomic} established.
 *
 * DI is intentionally NOT wired in production (FakeCsvColumnConfigStorage
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlCsvColumnConfigStorage implements CsvColumnConfigStorageInterface
{
    private const SAVEPOINT_NAME = 'sql_csv_column_config_replace';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<CsvColumnConfigEntity> sorted by sortNo */
    #[Override]
    public function listByType(int $csvType): array
    {
        $sql = 'SELECT csv_type_id, field_name, enabled, sort_no '
            . 'FROM dtb_csv '
            . 'WHERE csv_type_id = :csv_type '
            . 'ORDER BY sort_no ASC, id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':csv_type' => $csvType]);

        $entries = [];
        /** @var array<string, mixed> $row */
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $entries[] = $this->hydrate($row);
        }

        return $entries;
    }

    /** @param list<CsvColumnConfigEntity> $entries */
    #[Override]
    public function replaceType(int $csvType, array $entries): void
    {
        $this->withAtomic(function () use ($csvType, $entries): void {
            // DELETE all rows for this csvType — other csvTypes' rows
            // are out of scope. Then INSERT the new vector fresh. Using
            // DELETE+INSERT rather than a diff/UPSERT keeps the per-type
            // collection authoritative (no stale orphan rows).
            $delete = $this->pdo->prepare(
                'DELETE FROM dtb_csv WHERE csv_type_id = :csv_type',
            );
            $delete->execute([':csv_type' => $csvType]);

            if ($entries === []) {
                return;
            }

            $insert = $this->pdo->prepare(
                'INSERT INTO dtb_csv '
                . '(csv_type_id, creator_id, entity_name, field_name, '
                . 'reference_field_name, disp_name, sort_no, enabled, '
                . 'create_date, update_date, discriminator_type) '
                . 'VALUES (:csv_type, NULL, :entity_name, :field_name, '
                . 'NULL, :disp_name, :sort_no, :enabled, '
                . 'NOW(), NOW(), :discriminator)',
            );
            foreach ($entries as $entry) {
                $insert->execute([
                    ':csv_type' => $entry->csvType,
                    // entity_name / disp_name are NOT NULL in dtb_csv but
                    // not modeled by the Wave 9 Entity — echo field_name
                    // (the column catalog that supplies the real values
                    // is Phase 2).
                    ':entity_name' => $entry->columnName,
                    ':field_name' => $entry->columnName,
                    ':disp_name' => $entry->columnName,
                    ':sort_no' => $entry->sortNo,
                    ':enabled' => $entry->enabled ? 1 : 0,
                    ':discriminator' => 'csv',
                ]);
            }
        });
    }

    /** @param array<string, mixed> $row dtb_csv columns. */
    private function hydrate(array $row): CsvColumnConfigEntity
    {
        return new CsvColumnConfigEntity(
            csvType: (int) $row['csv_type_id'],
            columnName: (string) $row['field_name'],
            enabled: (bool) $row['enabled'],
            sortNo: (int) $row['sort_no'],
        );
    }

    /**
     * Run $work in either a fresh transaction (production) or a
     * SAVEPOINT (test, when the suite has already opened a tx).
     *
     * Throws propagate out — callers do not catch replace failures, the
     * exception will bubble up through the Final.
     */
    private function withAtomic(callable $work): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->exec('SAVEPOINT ' . self::SAVEPOINT_NAME);
            try {
                $work();
                $this->pdo->exec('RELEASE SAVEPOINT ' . self::SAVEPOINT_NAME);
            } catch (Throwable $e) {
                $this->pdo->exec('ROLLBACK TO SAVEPOINT ' . self::SAVEPOINT_NAME);

                throw $e;
            }

            return;
        }

        $this->pdo->beginTransaction();
        try {
            $work();
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }
}
