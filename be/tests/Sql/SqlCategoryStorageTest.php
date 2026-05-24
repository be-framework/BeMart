<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\CategoryEntity;
use MyVendor\BeMart\Be\Reason\Query\CategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\CategoryIdGeneratorInterface;

/**
 * Storage-layer coverage for {@see CategoryStorageInterface} (Phase 2b).
 *
 * Mirrors the shape of {@see BlockStorageInterfaceTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminCategoryResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip,
 * plus the two structural concerns specific to dtb_category: the
 * self-referential parent FK (hierarchy derivation, child ordering)
 * and the dtb_product_category cascade on remove.
 */
final class SqlCategoryStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInSortNoThenIdOrder(): void
    {
        // Mixed sort_no so the (sort_no asc, id asc) ordering is
        // observable — insertion order is deliberately not sort order.
        $third = $this->insertCategory(['category_name' => 'Drinks', 'sort_no' => 30]);
        $first = $this->insertCategory(['category_name' => 'Food', 'sort_no' => 10]);
        $second = $this->insertCategory(['category_name' => 'Snacks', 'sort_no' => 20]);

        $storage = $this->sql(CategoryStorageInterface::class);
        $rows = $storage->list();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(CategoryEntity::class, $rows);
        $this->assertSame((string) $first, $rows[0]->categoryId);
        $this->assertSame((string) $second, $rows[1]->categoryId);
        $this->assertSame((string) $third, $rows[2]->categoryId);
        $this->assertSame('Food', $rows[0]->categoryName);
        $this->assertSame('Snacks', $rows[1]->categoryName);
        $this->assertSame('Drinks', $rows[2]->categoryName);
    }

    public function testListTieBreaksEqualSortNoById(): void
    {
        // Equal sort_no → id ASC is the stable tie-break, matching the
        // Fake's (sortNo asc, categoryId asc) projection.
        $a = $this->insertCategory(['category_name' => 'A', 'sort_no' => 5]);
        $b = $this->insertCategory(['category_name' => 'B', 'sort_no' => 5]);

        $storage = $this->sql(CategoryStorageInterface::class);
        $rows = $storage->list();

        $this->assertSame((string) $a, $rows[0]->categoryId);
        $this->assertSame((string) $b, $rows[1]->categoryId);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = $this->sql(CategoryStorageInterface::class);
        $this->assertSame([], $storage->list());
    }

    public function testGetByIdReturnsHydratedRootEntity(): void
    {
        $id = $this->insertCategory([
            'category_name' => '食品',
            'sort_no' => 7,
        ]);

        $storage = $this->sql(CategoryStorageInterface::class);
        $entity = $storage->item((string) $id);

        $this->assertInstanceOf(CategoryEntity::class, $entity);
        $this->assertSame((string) $id, $entity->categoryId);
        $this->assertSame('食品', $entity->categoryName);
        $this->assertNull($entity->parentId);
        $this->assertSame(7, $entity->sortNo);
    }

    public function testGetByIdHydratesParentIdForChildRow(): void
    {
        // Self-FK ordering: parent must exist before the child INSERT.
        $parent = $this->insertCategory(['category_name' => 'Food', 'hierarchy' => 1]);
        $child = $this->insertCategory([
            'category_name' => 'Cookies',
            'parent_category_id' => $parent,
            'hierarchy' => 2,
        ]);

        $storage = $this->sql(CategoryStorageInterface::class);
        $entity = $storage->item((string) $child);

        $this->assertInstanceOf(CategoryEntity::class, $entity);
        $this->assertSame((string) $parent, $entity->parentId);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = $this->sql(CategoryStorageInterface::class);
        $this->assertNull($storage->item('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // The hex ids from FakeCategoryIdGenerator and seeds like
        // `nonexistent-zzz` can never match an int PK; surface as miss
        // so CategoryUpdated / CategoryDeleted fire their 404 paths
        // instead of a PDO error.
        $storage = $this->sql(CategoryStorageInterface::class);
        $this->assertNull($storage->item('deadbeefdeadbeefdeadbeefdeadbeef'));
        $this->assertNull($storage->item('nonexistent-zzz'));
    }

    public function testPutInsertsNewRootRowWithProvidedId(): void
    {
        $generator = $this->sql(CategoryIdGeneratorInterface::class);
        $newId = $generator->next()->value; // numeric string

        $entity = new CategoryEntity(
            categoryId: $newId,
            categoryName: 'Food',
            parentId: null,
            sortNo: 10,
        );

        $storage = $this->sql(CategoryStorageInterface::class);
        $storage->put($entity);

        $read = $storage->item($newId);
        $this->assertInstanceOf(CategoryEntity::class, $read);
        $this->assertSame($newId, $read->categoryId);
        $this->assertSame('Food', $read->categoryName);
        $this->assertNull($read->parentId);
        $this->assertSame(10, $read->sortNo);

        // list() also sees it.
        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame($newId, $all[0]->categoryId);
    }

    public function testPutInsertsRootRowWithHierarchyOne(): void
    {
        // hierarchy is NOT NULL with no DEFAULT — a root INSERT must
        // write depth 1. The projection never reads it, so probe the
        // raw column directly.
        $generator = $this->sql(CategoryIdGeneratorInterface::class);
        $newId = $generator->next()->value;
        $storage = $this->sql(CategoryStorageInterface::class);

        $storage->put(new CategoryEntity(
            categoryId: $newId,
            categoryName: 'Root',
            parentId: null,
            sortNo: 0,
        ));

        $stmt = $this->pdo->prepare(
            'SELECT hierarchy FROM dtb_category WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testPutDerivesChildHierarchyFromParent(): void
    {
        // A child INSERT derives hierarchy = parent.hierarchy + 1.
        // Build a three-level tree (root=1, child=2, grandchild=3) so
        // the cascade of derivation is observable.
        $rootId = $this->insertCategory(['category_name' => 'Food', 'hierarchy' => 1]);
        $storage = $this->sql(CategoryStorageInterface::class);
        $gen = $this->sql(CategoryIdGeneratorInterface::class);

        $childId = $gen->next()->value;
        $storage->put(new CategoryEntity(
            categoryId: $childId,
            categoryName: 'Cookies',
            parentId: (string) $rootId,
            sortNo: 0,
        ));

        $grandchildId = $gen->next()->value;
        $storage->put(new CategoryEntity(
            categoryId: $grandchildId,
            categoryName: 'Chocolate',
            parentId: $childId,
            sortNo: 0,
        ));

        $probe = $this->pdo->prepare(
            'SELECT hierarchy FROM dtb_category WHERE id = :id',
        );
        $probe->execute([':id' => (int) $childId]);
        $this->assertSame(2, (int) $probe->fetchColumn());
        $probe->execute([':id' => (int) $grandchildId]);
        $this->assertSame(3, (int) $probe->fetchColumn());

        // The parentId round-trips through the projection too.
        $read = $storage->item($grandchildId);
        $this->assertInstanceOf(CategoryEntity::class, $read);
        $this->assertSame($childId, $read->parentId);
    }

    public function testPutIsNoOpForNonNumericId(): void
    {
        $storage = $this->sql(CategoryStorageInterface::class);

        $storage->put(new CategoryEntity(
            categoryId: 'deadbeefdeadbeefdeadbeefdeadbeef',
            categoryName: 'Fake-shaped id',
            parentId: null,
            sortNo: 0,
        ));

        $this->assertSame([], $storage->list());
    }

    public function testPutFoldsNonNumericParentIdToRoot(): void
    {
        // A non-numeric parentId can never reference a real int PK —
        // the storage stores it as NULL (root) rather than raising.
        $generator = $this->sql(CategoryIdGeneratorInterface::class);
        $newId = $generator->next()->value;
        $storage = $this->sql(CategoryStorageInterface::class);

        $storage->put(new CategoryEntity(
            categoryId: $newId,
            categoryName: 'Orphan',
            parentId: 'nonexistent-zzz',
            sortNo: 0,
        ));

        $read = $storage->item($newId);
        $this->assertInstanceOf(CategoryEntity::class, $read);
        $this->assertNull($read->parentId);
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        // Insert via fixture (so id is known) then re-put via storage
        // with the same id — exercises the UPDATE branch. ALPS defines
        // doUpdateCategory so the UPDATE path is driven by normal admin
        // flows (UpdateCategoryInput / CategoryUpdated).
        $id = $this->insertCategory([
            'category_name' => 'Food',
            'sort_no' => 10,
        ]);

        $merged = new CategoryEntity(
            categoryId: (string) $id,
            categoryName: 'Foods',
            parentId: null,
            sortNo: 25,
        );

        $storage = $this->sql(CategoryStorageInterface::class);
        $storage->put($merged);

        $read = $storage->item((string) $id);
        $this->assertInstanceOf(CategoryEntity::class, $read);
        $this->assertSame('Foods', $read->categoryName);
        $this->assertSame(25, $read->sortNo);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testPutUpdateReparentsAndRederivesHierarchy(): void
    {
        // Re-parenting a node via UPDATE re-derives its hierarchy from
        // the new parent. A root (hierarchy 1) moved under another
        // root becomes hierarchy 2.
        $parentId = $this->insertCategory(['category_name' => 'Food', 'hierarchy' => 1]);
        $movingId = $this->insertCategory(['category_name' => 'Cookies', 'hierarchy' => 1]);

        $storage = $this->sql(CategoryStorageInterface::class);
        $storage->put(new CategoryEntity(
            categoryId: (string) $movingId,
            categoryName: 'Cookies',
            parentId: (string) $parentId,
            sortNo: 0,
        ));

        $read = $storage->item((string) $movingId);
        $this->assertInstanceOf(CategoryEntity::class, $read);
        $this->assertSame((string) $parentId, $read->parentId);

        $probe = $this->pdo->prepare(
            'SELECT hierarchy FROM dtb_category WHERE id = :id',
        );
        $probe->execute([':id' => $movingId]);
        $this->assertSame(2, (int) $probe->fetchColumn());
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $id = $this->insertCategory(['category_name' => 'doomed']);
        $storage = $this->sql(CategoryStorageInterface::class);
        $this->assertNotNull($storage->item((string) $id));

        $storage->delete((string) $id);

        $this->assertNull($storage->item((string) $id));
        $this->assertSame([], $storage->list());
    }

    public function testRemoveCascadesDtbProductCategoryAssignments(): void
    {
        // dtb_product_category's FK (category_id → dtb_category.id)
        // would otherwise raise FK 1451 on the category DELETE.
        // CategoryStorageInterface::remove pre-DELETEs the assignment rows so
        // the category-level delete succeeds regardless of assignment
        // state.
        $categoryId = $this->insertCategory(['category_name' => 'Sale']);
        $productId = $this->insertProduct(['name' => 'Test Product']);

        // Seed a product-category assignment row directly.
        $this->pdo->prepare(
            'INSERT INTO dtb_product_category '
            . '(product_id, category_id, discriminator_type) '
            . 'VALUES (:product_id, :category_id, :discriminator)',
        )->execute([
            ':product_id' => $productId,
            ':category_id' => $categoryId,
            ':discriminator' => 'productcategory',
        ]);

        $storage = $this->sql(CategoryStorageInterface::class);
        $storage->delete((string) $categoryId);

        // Category is gone.
        $this->assertNull($storage->item((string) $categoryId));

        // Assignment row is also gone (cleanup, not just FK satisfaction).
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_product_category WHERE category_id = :id',
        );
        $stmt->execute([':id' => $categoryId]);
        $this->assertSame(0, (int) $stmt->fetchColumn());

        // The product itself is untouched — only the assignment is dropped.
        $productProbe = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_product WHERE id = :id',
        );
        $productProbe->execute([':id' => $productId]);
        $this->assertSame(1, (int) $productProbe->fetchColumn());
    }

    public function testRemoveIsSilentNoOpForMissingId(): void
    {
        $storage = $this->sql(CategoryStorageInterface::class);
        $storage->delete('99999999'); // no row, no exception
        $storage->delete('deadbeefdeadbeefdeadbeefdeadbeef'); // non-numeric
        $storage->delete('nonexistent-zzz'); // non-numeric, no exception
        $this->assertTrue(true);
    }

    public function testCategoryIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = $this->sql(CategoryIdGeneratorInterface::class);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->next()->value);

        $firstId = $this->insertCategory();
        $secondId = $this->insertCategory();
        $this->assertSame((string) ($secondId + 1), $generator->next()->value);
        $this->assertGreaterThan($firstId, $secondId);
    }
}
