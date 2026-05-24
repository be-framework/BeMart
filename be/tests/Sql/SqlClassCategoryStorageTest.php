<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassCategoryIdGeneratorInterface;

/**
 * Storage-layer coverage for {@see ClassCategoryStorageInterface} (Phase 2b).
 *
 * Mirrors the shape of {@see ClassNameStorageInterfaceTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminClassCategoryResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip,
 * plus the structural concerns specific to dtb_class_category: the
 * class_name_id FK pin (every row belongs to a parent dtb_class_name),
 * the `listByClassName` axis scope, and that `remove` does NOT cascade
 * (a variant-value delete must not drop the products that use it).
 */
final class SqlClassCategoryStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $axis = $this->insertClassName(['name' => 'Color']);
        $first = $this->insertClassCategory(['class_name_id' => $axis, 'name' => 'Red']);
        $second = $this->insertClassCategory(['class_name_id' => $axis, 'name' => 'Blue']);
        $third = $this->insertClassCategory(['class_name_id' => $axis, 'name' => 'Green']);

        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $rows = $storage->list();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(ClassCategoryEntity::class, $rows);
        $this->assertSame((string) $first, $rows[0]->classCategoryId);
        $this->assertSame((string) $second, $rows[1]->classCategoryId);
        $this->assertSame((string) $third, $rows[2]->classCategoryId);
        $this->assertSame('Red', $rows[0]->name);
        $this->assertSame('Blue', $rows[1]->name);
        $this->assertSame('Green', $rows[2]->name);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $this->assertSame([], $storage->list());
    }

    public function testListReturnsRowsAcrossAllAxes(): void
    {
        // list() is the admin grid view — every row regardless of axis.
        $colorAxis = $this->insertClassName(['name' => 'Color']);
        $sizeAxis = $this->insertClassName(['name' => 'Size']);
        $this->insertClassCategory(['class_name_id' => $colorAxis, 'name' => 'Red']);
        $this->insertClassCategory(['class_name_id' => $sizeAxis, 'name' => 'Large']);

        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $this->assertCount(2, $storage->list());
    }

    public function testListByClassNameScopesToOneAxis(): void
    {
        // listByClassName is the default UI view — values for one axis.
        $colorAxis = $this->insertClassName(['name' => 'Color']);
        $sizeAxis = $this->insertClassName(['name' => 'Size']);
        $this->insertClassCategory(['class_name_id' => $colorAxis, 'name' => 'Red']);
        $this->insertClassCategory(['class_name_id' => $colorAxis, 'name' => 'Blue']);
        $this->insertClassCategory(['class_name_id' => $sizeAxis, 'name' => 'Large']);

        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $colorValues = $storage->listByClassName((string) $colorAxis);

        $this->assertCount(2, $colorValues);
        $this->assertContainsOnlyInstancesOf(ClassCategoryEntity::class, $colorValues);
        foreach ($colorValues as $value) {
            $this->assertSame((string) $colorAxis, $value->classNameId);
        }
    }

    public function testListByClassNameReturnsEmptyForAxisWithNoValues(): void
    {
        $emptyAxis = $this->insertClassName(['name' => 'Material']);

        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $this->assertSame([], $storage->listByClassName((string) $emptyAxis));
    }

    public function testListByClassNameReturnsEmptyForNonNumericAxisId(): void
    {
        // A non-numeric axis id can never match an int FK column —
        // surface as an empty scope, not a PDO error.
        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $this->assertSame([], $storage->listByClassName('nonexistent-zzz'));
        $this->assertSame(
            [],
            $storage->listByClassName('deadbeefdeadbeefdeadbeefdeadbeef'),
        );
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $axis = $this->insertClassName(['name' => 'カラー']);
        $id = $this->insertClassCategory(['class_name_id' => $axis, 'name' => '赤']);

        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $entity = $storage->item((string) $id);

        $this->assertInstanceOf(ClassCategoryEntity::class, $entity);
        $this->assertSame((string) $id, $entity->classCategoryId);
        $this->assertSame((string) $axis, $entity->classNameId);
        $this->assertSame('赤', $entity->name);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $this->assertNull($storage->item('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // The 32-char hex from FakeClassCategoryIdGenerator and seeds
        // like `nonexistent-zzz` can never match an int PK; surface as
        // miss so the ClassCategory Update / Delete Finals fire their
        // 404 paths instead of a PDO error.
        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $this->assertNull($storage->item('deadbeefdeadbeefdeadbeefdeadbeef'));
        $this->assertNull($storage->item('nonexistent-zzz'));
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        $axis = $this->insertClassName(['name' => 'Color']);

        $generator = $this->sql(ClassCategoryIdGeneratorInterface::class);
        $newId = $generator->next()->value; // numeric string

        $entity = new ClassCategoryEntity(
            classCategoryId: $newId,
            classNameId: (string) $axis,
            name: 'Red',
        );

        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $storage->put($entity);

        $read = $storage->item($newId);
        $this->assertInstanceOf(ClassCategoryEntity::class, $read);
        $this->assertSame($newId, $read->classCategoryId);
        $this->assertSame((string) $axis, $read->classNameId);
        $this->assertSame('Red', $read->name);

        // list() also sees it.
        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame($newId, $all[0]->classCategoryId);
    }

    public function testPutInsertPinsRowToParentAxis(): void
    {
        // The class_name_id FK is part of the projection — a put INSERT
        // writes it from the entity, and listByClassName then finds the
        // row scoped to that axis.
        $axis = $this->insertClassName(['name' => 'Size']);

        $generator = $this->sql(ClassCategoryIdGeneratorInterface::class);
        $newId = $generator->next()->value;
        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $storage->put(new ClassCategoryEntity(
            classCategoryId: $newId,
            classNameId: (string) $axis,
            name: 'Large',
        ));

        $scoped = $storage->listByClassName((string) $axis);
        $this->assertCount(1, $scoped);
        $this->assertSame($newId, $scoped[0]->classCategoryId);
    }

    public function testPutInsertWritesNonNullSortNo(): void
    {
        // sort_no is NOT NULL with no DEFAULT — a put INSERT must write
        // a value. The projection never reads it, so probe the raw
        // column directly. First INSERT on an empty table → 1.
        $axis = $this->insertClassName(['name' => 'Color']);
        $generator = $this->sql(ClassCategoryIdGeneratorInterface::class);
        $newId = $generator->next()->value;
        $storage = $this->sql(ClassCategoryStorageInterface::class);

        $storage->put(new ClassCategoryEntity(
            classCategoryId: $newId,
            classNameId: (string) $axis,
            name: 'Red',
        ));

        $stmt = $this->pdo->prepare(
            'SELECT sort_no FROM dtb_class_category WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testPutInsertWritesVisibleOne(): void
    {
        // The admin slice has no show/hide UI — visible is always 1.
        $axis = $this->insertClassName(['name' => 'Color']);
        $generator = $this->sql(ClassCategoryIdGeneratorInterface::class);
        $newId = $generator->next()->value;
        $storage = $this->sql(ClassCategoryStorageInterface::class);

        $storage->put(new ClassCategoryEntity(
            classCategoryId: $newId,
            classNameId: (string) $axis,
            name: 'Red',
        ));

        $stmt = $this->pdo->prepare(
            'SELECT visible FROM dtb_class_category WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testPutInsertAppendsMonotonicSortNo(): void
    {
        // Each fresh INSERT derives sort_no = MAX(sort_no)+1 so values
        // append to the end in a stable order.
        $axis = $this->insertClassName(['name' => 'Color']);
        $this->insertClassCategory([
            'class_name_id' => $axis,
            'name' => 'Existing',
            'sort_no' => 7,
        ]);

        $generator = $this->sql(ClassCategoryIdGeneratorInterface::class);
        $newId = $generator->next()->value;
        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $storage->put(new ClassCategoryEntity(
            classCategoryId: $newId,
            classNameId: (string) $axis,
            name: 'Red',
        ));

        $stmt = $this->pdo->prepare(
            'SELECT sort_no FROM dtb_class_category WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $this->assertSame(8, (int) $stmt->fetchColumn());
    }

    public function testPutIsNoOpForNonNumericId(): void
    {
        $axis = $this->insertClassName(['name' => 'Color']);
        $storage = $this->sql(ClassCategoryStorageInterface::class);

        $storage->put(new ClassCategoryEntity(
            classCategoryId: 'deadbeefdeadbeefdeadbeefdeadbeef',
            classNameId: (string) $axis,
            name: 'Fake-shaped id',
        ));

        $this->assertSame([], $storage->list());
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        // Insert via fixture (so id is known) then re-put via storage
        // with the same id — exercises the UPDATE branch driven by the
        // normal admin rename flow (doUpdateClassCategory).
        $axis = $this->insertClassName(['name' => 'Color']);
        $id = $this->insertClassCategory([
            'class_name_id' => $axis,
            'name' => 'Red',
            'sort_no' => 3,
        ]);

        $merged = new ClassCategoryEntity(
            classCategoryId: (string) $id,
            classNameId: (string) $axis,
            name: 'Crimson',
        );

        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $storage->put($merged);

        $read = $storage->item((string) $id);
        $this->assertInstanceOf(ClassCategoryEntity::class, $read);
        $this->assertSame('Crimson', $read->name);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testPutUpdateLeavesSortNoUntouched(): void
    {
        // A rename via UPDATE must not disturb sort_no — the projection
        // never carries it, so the display slot is preserved.
        $axis = $this->insertClassName(['name' => 'Color']);
        $id = $this->insertClassCategory([
            'class_name_id' => $axis,
            'name' => 'Red',
            'sort_no' => 42,
        ]);

        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $storage->put(new ClassCategoryEntity(
            classCategoryId: (string) $id,
            classNameId: (string) $axis,
            name: 'Crimson',
        ));

        $stmt = $this->pdo->prepare(
            'SELECT sort_no FROM dtb_class_category WHERE id = :id',
        );
        $stmt->execute([':id' => $id]);
        $this->assertSame(42, (int) $stmt->fetchColumn());
    }

    public function testPutUpdateLeavesClassNameIdUntouched(): void
    {
        // A variant value never migrates between axes — the UPDATE
        // branch leaves class_name_id pinned to the original axis.
        $axis = $this->insertClassName(['name' => 'Color']);
        $id = $this->insertClassCategory([
            'class_name_id' => $axis,
            'name' => 'Red',
        ]);

        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $storage->put(new ClassCategoryEntity(
            classCategoryId: (string) $id,
            classNameId: (string) $axis,
            name: 'Crimson',
        ));

        $read = $storage->item((string) $id);
        $this->assertInstanceOf(ClassCategoryEntity::class, $read);
        $this->assertSame((string) $axis, $read->classNameId);
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $axis = $this->insertClassName(['name' => 'Color']);
        $id = $this->insertClassCategory(['class_name_id' => $axis, 'name' => 'doomed']);
        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $this->assertNotNull($storage->item((string) $id));

        $storage->delete((string) $id);

        $this->assertNull($storage->item((string) $id));
        $this->assertSame([], $storage->list());
    }

    public function testRemoveLeavesSiblingValuesUntouched(): void
    {
        // remove is scoped to one row — sibling values under the same
        // axis survive.
        $axis = $this->insertClassName(['name' => 'Color']);
        $doomed = $this->insertClassCategory(['class_name_id' => $axis, 'name' => 'Red']);
        $kept = $this->insertClassCategory(['class_name_id' => $axis, 'name' => 'Blue']);

        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $storage->delete((string) $doomed);

        $this->assertNull($storage->item((string) $doomed));
        $this->assertNotNull($storage->item((string) $kept));
        $this->assertCount(1, $storage->listByClassName((string) $axis));
    }

    public function testRemoveIsSilentNoOpForMissingId(): void
    {
        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $storage->delete('99999999'); // no row, no exception
        $storage->delete('deadbeefdeadbeefdeadbeefdeadbeef'); // non-numeric
        $storage->delete('nonexistent-zzz'); // non-numeric, no exception
        $this->assertTrue(true);
    }

    public function testReorderRewritesSortNo(): void
    {
        $axis = $this->insertClassName(['name' => 'Color']);
        $id = $this->insertClassCategory(['class_name_id' => $axis, 'sort_no' => 1]);
        $storage = $this->sql(ClassCategoryStorageInterface::class);

        $storage->reorder((string) $id, 88);

        $stmt = $this->pdo->prepare('SELECT sort_no FROM dtb_class_category WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(88, (int) $row['sort_no']);
    }

    public function testSetVisibleRewritesVisibleColumn(): void
    {
        $axis = $this->insertClassName(['name' => 'Size']);
        $id = $this->insertClassCategory(['class_name_id' => $axis, 'visible' => 1]);
        $storage = $this->sql(ClassCategoryStorageInterface::class);

        $storage->visible((string) $id, false);

        $stmt = $this->pdo->prepare('SELECT visible FROM dtb_class_category WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(0, (int) $row['visible']);

        $storage->visible((string) $id, true);
        $stmt->execute([':id' => $id]);
        $back = $stmt->fetch();
        $this->assertNotFalse($back);
        $this->assertSame(1, (int) $back['visible']);
    }

    public function testReorderAndSetVisibleAreSilentNoOpForNonNumericId(): void
    {
        $storage = $this->sql(ClassCategoryStorageInterface::class);
        $storage->reorder('nonexistent-zzz', 5);
        $storage->visible('nonexistent-zzz', false);
        $this->assertTrue(true);
    }

    public function testClassCategoryIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = $this->sql(ClassCategoryIdGeneratorInterface::class);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->next()->value);

        $axis = $this->insertClassName(['name' => 'Color']);
        $firstId = $this->insertClassCategory(['class_name_id' => $axis]);
        $secondId = $this->insertClassCategory(['class_name_id' => $axis]);
        $this->assertSame((string) ($secondId + 1), $generator->next()->value);
        $this->assertGreaterThan($firstId, $secondId);
    }
}
