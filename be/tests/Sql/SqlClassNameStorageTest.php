<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\ClassNameIdGeneratorInterface;

/**
 * Storage-layer coverage for {@see ClassNameStorageInterface} (Phase 2b).
 *
 * Mirrors the shape of {@see CategoryStorageInterfaceTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminClassNameResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip,
 * plus the one structural concern specific to dtb_class_name: the
 * child dtb_class_category cascade on remove (FK_9B0D1DBAB462FB2A).
 */
final class SqlClassNameStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        // Insertion order is the id order here (autoinc) — list()'s
        // `ORDER BY id ASC` mirrors the Fake's ksort on the id key.
        $first = $this->insertClassName(['name' => 'Color']);
        $second = $this->insertClassName(['name' => 'Size']);
        $third = $this->insertClassName(['name' => 'Material']);

        $storage = $this->sql(ClassNameStorageInterface::class);
        $rows = $storage->list();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(ClassNameEntity::class, $rows);
        $this->assertSame((string) $first, $rows[0]->classNameId);
        $this->assertSame((string) $second, $rows[1]->classNameId);
        $this->assertSame((string) $third, $rows[2]->classNameId);
        $this->assertSame('Color', $rows[0]->name);
        $this->assertSame('Size', $rows[1]->name);
        $this->assertSame('Material', $rows[2]->name);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = $this->sql(ClassNameStorageInterface::class);
        $this->assertSame([], $storage->list());
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $id = $this->insertClassName(['name' => 'カラー']);

        $storage = $this->sql(ClassNameStorageInterface::class);
        $entity = $storage->item((string) $id);

        $this->assertInstanceOf(ClassNameEntity::class, $entity);
        $this->assertSame((string) $id, $entity->classNameId);
        $this->assertSame('カラー', $entity->name);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = $this->sql(ClassNameStorageInterface::class);
        $this->assertNull($storage->item('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // The 32-char hex from FakeClassNameIdGenerator and seeds like
        // `nonexistent-zzz` can never match an int PK; surface as miss
        // so the ClassName Update / Delete Finals fire their 404 paths
        // instead of a PDO error.
        $storage = $this->sql(ClassNameStorageInterface::class);
        $this->assertNull($storage->item('deadbeefdeadbeefdeadbeefdeadbeef'));
        $this->assertNull($storage->item('nonexistent-zzz'));
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        $generator = $this->sql(ClassNameIdGeneratorInterface::class);
        $newId = $generator->next()->value; // numeric string

        $entity = new ClassNameEntity(
            classNameId: $newId,
            name: 'Color',
        );

        $storage = $this->sql(ClassNameStorageInterface::class);
        $storage->put($entity);

        $read = $storage->item($newId);
        $this->assertInstanceOf(ClassNameEntity::class, $read);
        $this->assertSame($newId, $read->classNameId);
        $this->assertSame('Color', $read->name);

        // list() also sees it.
        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame($newId, $all[0]->classNameId);
    }

    public function testPutInsertWritesNonNullSortNo(): void
    {
        // sort_no is NOT NULL with no DEFAULT — a put INSERT must write
        // a value. The projection never reads it, so probe the raw
        // column directly. First INSERT on an empty table → 1.
        $generator = $this->sql(ClassNameIdGeneratorInterface::class);
        $newId = $generator->next()->value;
        $storage = $this->sql(ClassNameStorageInterface::class);

        $storage->put(new ClassNameEntity(classNameId: $newId, name: 'Color'));

        $stmt = $this->pdo->prepare(
            'SELECT sort_no FROM dtb_class_name WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testPutInsertAppendsMonotonicSortNo(): void
    {
        // Each fresh INSERT derives sort_no = MAX(sort_no)+1 so axes
        // append to the end in a stable order.
        $this->insertClassName(['name' => 'Existing', 'sort_no' => 7]);

        $generator = $this->sql(ClassNameIdGeneratorInterface::class);
        $newId = $generator->next()->value;
        $storage = $this->sql(ClassNameStorageInterface::class);
        $storage->put(new ClassNameEntity(classNameId: $newId, name: 'Color'));

        $stmt = $this->pdo->prepare(
            'SELECT sort_no FROM dtb_class_name WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $this->assertSame(8, (int) $stmt->fetchColumn());
    }

    public function testPutIsNoOpForNonNumericId(): void
    {
        $storage = $this->sql(ClassNameStorageInterface::class);

        $storage->put(new ClassNameEntity(
            classNameId: 'deadbeefdeadbeefdeadbeefdeadbeef',
            name: 'Fake-shaped id',
        ));

        $this->assertSame([], $storage->list());
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        // Insert via fixture (so id is known) then re-put via storage
        // with the same id — exercises the UPDATE branch driven by the
        // normal admin rename flow (doUpdateClassName).
        $id = $this->insertClassName(['name' => 'Color', 'sort_no' => 3]);

        $merged = new ClassNameEntity(
            classNameId: (string) $id,
            name: 'Colour',
        );

        $storage = $this->sql(ClassNameStorageInterface::class);
        $storage->put($merged);

        $read = $storage->item((string) $id);
        $this->assertInstanceOf(ClassNameEntity::class, $read);
        $this->assertSame('Colour', $read->name);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testPutUpdateLeavesSortNoUntouched(): void
    {
        // A rename via UPDATE must not disturb sort_no — the projection
        // never carries it, so the display slot is preserved.
        $id = $this->insertClassName(['name' => 'Color', 'sort_no' => 42]);

        $storage = $this->sql(ClassNameStorageInterface::class);
        $storage->put(new ClassNameEntity(classNameId: (string) $id, name: 'Colour'));

        $stmt = $this->pdo->prepare(
            'SELECT sort_no FROM dtb_class_name WHERE id = :id',
        );
        $stmt->execute([':id' => $id]);
        $this->assertSame(42, (int) $stmt->fetchColumn());
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $id = $this->insertClassName(['name' => 'doomed']);
        $storage = $this->sql(ClassNameStorageInterface::class);
        $this->assertNotNull($storage->item((string) $id));

        $storage->delete((string) $id);

        $this->assertNull($storage->item((string) $id));
        $this->assertSame([], $storage->list());
    }

    public function testRemoveCascadesChildClassCategoryRows(): void
    {
        // dtb_class_category's FK (class_name_id → dtb_class_name.id,
        // FK_9B0D1DBAB462FB2A) would otherwise raise FK 1451 on the
        // class_name DELETE. ClassNameStorageInterface::remove pre-DELETEs the
        // child axis-value rows so the axis-level delete succeeds
        // regardless of child state — keeping the SQL `remove`
        // always-succeeds shape identical to the Fake's `unset()`.
        $classNameId = $this->insertClassName(['name' => 'Color']);
        $this->insertClassCategory(['class_name_id' => $classNameId, 'name' => 'Red']);
        $this->insertClassCategory(['class_name_id' => $classNameId, 'name' => 'Blue']);

        $storage = $this->sql(ClassNameStorageInterface::class);
        $storage->delete((string) $classNameId);

        // Axis is gone.
        $this->assertNull($storage->item((string) $classNameId));

        // Child axis-value rows are also gone (cleanup, not just FK
        // satisfaction).
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_class_category WHERE class_name_id = :id',
        );
        $stmt->execute([':id' => $classNameId]);
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function testRemoveLeavesUnrelatedClassCategoryRowsUntouched(): void
    {
        // The cascade is scoped to the deleted axis only — class_category
        // rows under a different axis survive.
        $doomedAxis = $this->insertClassName(['name' => 'Color']);
        $keptAxis = $this->insertClassName(['name' => 'Size']);
        $this->insertClassCategory(['class_name_id' => $doomedAxis, 'name' => 'Red']);
        $keptValue = $this->insertClassCategory([
            'class_name_id' => $keptAxis,
            'name' => 'Large',
        ]);

        $storage = $this->sql(ClassNameStorageInterface::class);
        $storage->delete((string) $doomedAxis);

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_class_category WHERE id = :id',
        );
        $stmt->execute([':id' => $keptValue]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testRemoveIsSilentNoOpForMissingId(): void
    {
        $storage = $this->sql(ClassNameStorageInterface::class);
        $storage->delete('99999999'); // no row, no exception
        $storage->delete('deadbeefdeadbeefdeadbeefdeadbeef'); // non-numeric
        $storage->delete('nonexistent-zzz'); // non-numeric, no exception
        $this->assertTrue(true);
    }

    public function testReorderRewritesSortNo(): void
    {
        $id = $this->insertClassName(['name' => 'Color', 'sort_no' => 2]);
        $storage = $this->sql(ClassNameStorageInterface::class);

        $storage->reorder((string) $id, 42);

        $stmt = $this->pdo->prepare('SELECT sort_no FROM dtb_class_name WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(42, (int) $row['sort_no']);
    }

    public function testReorderIsSilentNoOpForNonNumericId(): void
    {
        $storage = $this->sql(ClassNameStorageInterface::class);
        $storage->reorder('nonexistent-zzz', 5); // non-numeric, no exception
        $this->assertTrue(true);
    }

    public function testClassNameIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = $this->sql(ClassNameIdGeneratorInterface::class);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->next()->value);

        $firstId = $this->insertClassName();
        $secondId = $this->insertClassName();
        $this->assertSame((string) ($secondId + 1), $generator->next()->value);
        $this->assertGreaterThan($firstId, $secondId);
    }
}
