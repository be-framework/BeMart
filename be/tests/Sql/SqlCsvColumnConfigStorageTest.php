<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;
use MyVendor\BeMart\Be\Reason\Query\Param\CsvColumnConfigList;
use MyVendor\BeMart\Be\Reason\Query\CsvColumnConfigStorageInterface;

/**
 * Storage-layer coverage for {@see CsvColumnConfigStorageInterface} (Phase 2b).
 *
 * Per G-23 the client-observable contract lives in the Resource-layer
 * sibling ({@see \MyVendor\BeMart\Tests\Resource\Sql\AdminCsvConfigResourceSqlTest});
 * the cases below pin the per-method SQL paths in isolation.
 *
 * Surprises this suite locks in:
 *  - Each `dtb_csv` row is ONE column-config entry; a csvType owns many
 *    rows. `csvType` ← `csv_type_id`, `columnName` ← `field_name`,
 *    `enabled` ← `enabled`, `sortNo` ← `sort_no`.
 *  - `csv_type_id` is an enforced FK to the EMPTY mtb_csv_type master —
 *    without seedCsvTypes a replaceType INSERT raises FK 1452.
 *  - `replaceType` is atomic per-type: it DELETEs every row for the
 *    csvType then INSERTs the new vector. A replace within a populated
 *    DB leaves OTHER csvTypes' rows untouched (the DELETE is scoped
 *    `WHERE csv_type_id = :csv_type`).
 *  - `entity_name` / `disp_name` are NOT NULL in dtb_csv but not modeled
 *    by the Wave 9 Entity — the INSERT echoes `field_name` into them.
 */
final class SqlCsvColumnConfigStorageTest extends AbstractSqlTestCase
{
    public function testListByTypeReturnsEmptyWhenNoRows(): void
    {
        $storage = $this->sql(CsvColumnConfigStorageInterface::class);
        $this->assertSame([], $storage->listByType(3));
    }

    public function testListByTypeReturnsRowsSortedBySortNo(): void
    {
        $this->seedCsvTypes();
        // Insert out of sort order — listByType must return sorted.
        $this->insertCsvColumn(['csv_type_id' => 3, 'field_name' => 'note', 'sort_no' => 3]);
        $this->insertCsvColumn(['csv_type_id' => 3, 'field_name' => 'code', 'sort_no' => 1]);
        $this->insertCsvColumn(['csv_type_id' => 3, 'field_name' => 'name', 'sort_no' => 2]);

        $storage = $this->sql(CsvColumnConfigStorageInterface::class);
        $rows = $storage->listByType(3);

        $this->assertCount(3, $rows);
        $this->assertSame('code', $rows[0]->columnName);
        $this->assertSame('name', $rows[1]->columnName);
        $this->assertSame('note', $rows[2]->columnName);
    }

    public function testListByTypeHydratesEveryField(): void
    {
        $this->seedCsvTypes();
        $this->insertCsvColumn([
            'csv_type_id' => 1,
            'field_name' => 'orderNo',
            'sort_no' => 7,
            'enabled' => 0,
        ]);

        $storage = $this->sql(CsvColumnConfigStorageInterface::class);
        $rows = $storage->listByType(1);

        $this->assertCount(1, $rows);
        $this->assertInstanceOf(CsvColumnConfigEntity::class, $rows[0]);
        $this->assertSame(1, $rows[0]->csvType);
        $this->assertSame('orderNo', $rows[0]->columnName);
        $this->assertFalse($rows[0]->enabled);
        $this->assertSame(7, $rows[0]->sortNo);
    }

    public function testListByTypeIgnoresOtherCsvTypes(): void
    {
        $this->seedCsvTypes();
        $this->insertCsvColumn(['csv_type_id' => 3, 'field_name' => 'productCode']);
        $this->insertCsvColumn(['csv_type_id' => 1, 'field_name' => 'orderNo']);

        $storage = $this->sql(CsvColumnConfigStorageInterface::class);
        $product = $storage->listByType(3);

        $this->assertCount(1, $product);
        $this->assertSame('productCode', $product[0]->columnName);
    }

    public function testReplaceTypePersistsTheVector(): void
    {
        $this->seedCsvTypes();
        $storage = $this->sql(CsvColumnConfigStorageInterface::class);

        $storage->replaceType(3, CsvColumnConfigList::fromArray([
            new CsvColumnConfigEntity(csvType: 3, columnName: 'productCode', enabled: true, sortNo: 1),
            new CsvColumnConfigEntity(csvType: 3, columnName: 'productName', enabled: true, sortNo: 2),
            new CsvColumnConfigEntity(csvType: 3, columnName: 'note', enabled: false, sortNo: 3),
        ]));

        $rows = $storage->listByType(3);
        $this->assertCount(3, $rows);
        $this->assertSame('productCode', $rows[0]->columnName);
        $this->assertTrue($rows[0]->enabled);
        $this->assertSame('note', $rows[2]->columnName);
        $this->assertFalse($rows[2]->enabled);
        $this->assertSame(3, $rows[2]->sortNo);
    }

    public function testReplaceTypeWithEmptyVectorClearsTheType(): void
    {
        $this->seedCsvTypes();
        $storage = $this->sql(CsvColumnConfigStorageInterface::class);

        $storage->replaceType(3, CsvColumnConfigList::fromArray([
            new CsvColumnConfigEntity(csvType: 3, columnName: 'productCode', enabled: true, sortNo: 1),
        ]));
        $this->assertCount(1, $storage->listByType(3));

        // An empty vector means "no columns for this type" — the DELETE
        // still runs, the INSERT is skipped.
        $storage->replaceType(3, CsvColumnConfigList::fromArray([]));
        $this->assertSame([], $storage->listByType(3));
    }

    public function testReplaceTypeRemovesOldRowsAndInsertsNewOnesAtomically(): void
    {
        $this->seedCsvTypes();
        $storage = $this->sql(CsvColumnConfigStorageInterface::class);

        // First write: 2 columns.
        $storage->replaceType(1, CsvColumnConfigList::fromArray([
            new CsvColumnConfigEntity(csvType: 1, columnName: 'orderNo', enabled: true, sortNo: 1),
            new CsvColumnConfigEntity(csvType: 1, columnName: 'total', enabled: true, sortNo: 2),
        ]));
        $this->assertCount(2, $storage->listByType(1));

        // Second write: a different single column — old rows must be
        // gone, the new row must be present (no merge).
        $storage->replaceType(1, CsvColumnConfigList::fromArray([
            new CsvColumnConfigEntity(csvType: 1, columnName: 'orderDate', enabled: true, sortNo: 1),
        ]));

        $rows = $storage->listByType(1);
        $this->assertCount(1, $rows);
        $this->assertSame('orderDate', $rows[0]->columnName);

        // Old column names are gone — assert by absence.
        $names = array_map(
            static fn (CsvColumnConfigEntity $e): string => $e->columnName,
            $rows,
        );
        $this->assertNotContains('orderNo', $names);
        $this->assertNotContains('total', $names);
    }

    public function testReplaceTypeLeavesOtherCsvTypesUntouched(): void
    {
        $this->seedCsvTypes();
        $storage = $this->sql(CsvColumnConfigStorageInterface::class);

        // Populate two distinct csvTypes.
        $storage->replaceType(1, CsvColumnConfigList::fromArray([
            new CsvColumnConfigEntity(csvType: 1, columnName: 'orderNo', enabled: true, sortNo: 1),
            new CsvColumnConfigEntity(csvType: 1, columnName: 'total', enabled: true, sortNo: 2),
        ]));
        $storage->replaceType(3, CsvColumnConfigList::fromArray([
            new CsvColumnConfigEntity(csvType: 3, columnName: 'productCode', enabled: true, sortNo: 1),
        ]));

        // Replace csvType 1 — csvType 3's vector must survive intact.
        $storage->replaceType(1, CsvColumnConfigList::fromArray([
            new CsvColumnConfigEntity(csvType: 1, columnName: 'orderDate', enabled: false, sortNo: 1),
        ]));

        $type3 = $storage->listByType(3);
        $this->assertCount(1, $type3);
        $this->assertSame('productCode', $type3[0]->columnName);
        $this->assertTrue($type3[0]->enabled);

        $type1 = $storage->listByType(1);
        $this->assertCount(1, $type1);
        $this->assertSame('orderDate', $type1[0]->columnName);
        $this->assertFalse($type1[0]->enabled);
    }

    public function testReplaceTypeReplacesPreExistingFixtureRows(): void
    {
        // Rows seeded directly (not through the storage) for csvType 3
        // must also be wiped by a replaceType — replace is keyed on
        // csv_type_id, not on "rows this storage wrote".
        $this->seedCsvTypes();
        $this->insertCsvColumn(['csv_type_id' => 3, 'field_name' => 'legacyA', 'sort_no' => 1]);
        $this->insertCsvColumn(['csv_type_id' => 3, 'field_name' => 'legacyB', 'sort_no' => 2]);

        $storage = $this->sql(CsvColumnConfigStorageInterface::class);
        $this->assertCount(2, $storage->listByType(3));

        $storage->replaceType(3, CsvColumnConfigList::fromArray([
            new CsvColumnConfigEntity(csvType: 3, columnName: 'fresh', enabled: true, sortNo: 1),
        ]));

        $rows = $storage->listByType(3);
        $this->assertCount(1, $rows);
        $this->assertSame('fresh', $rows[0]->columnName);
    }

    public function testReplaceTypeIsIdempotentOnIdenticalVector(): void
    {
        // Replaying the same vector lands the same row set — the
        // doUpdateCsv idempotency contract.
        $this->seedCsvTypes();
        $storage = $this->sql(CsvColumnConfigStorageInterface::class);

        $vector = [
            new CsvColumnConfigEntity(csvType: 2, columnName: 'email', enabled: true, sortNo: 1),
            new CsvColumnConfigEntity(csvType: 2, columnName: 'name', enabled: false, sortNo: 2),
        ];

        $storage->replaceType(2, CsvColumnConfigList::fromArray($vector));
        $storage->replaceType(2, CsvColumnConfigList::fromArray($vector));

        $rows = $storage->listByType(2);
        $this->assertCount(2, $rows);
        $this->assertSame('email', $rows[0]->columnName);
        $this->assertSame('name', $rows[1]->columnName);
        $this->assertFalse($rows[1]->enabled);
    }
}
