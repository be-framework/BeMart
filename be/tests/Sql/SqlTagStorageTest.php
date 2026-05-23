<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlTagStorage;
use MyVendor\BeMart\Be\Reason\Service\TagIdGeneratorInterface;

/**
 * Storage-layer coverage for {@see SqlTagStorage} (Phase 2b).
 *
 * Mirrors the shape of {@see SqlAddressStorageTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminTagResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation.
 */
final class SqlTagStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $firstId = $this->insertTag(['name' => 'New Arrivals']);
        $secondId = $this->insertTag(['name' => 'Sale']);
        $thirdId = $this->insertTag(['name' => 'Limited']);

        $storage = $this->sql(SqlTagStorage::class);
        $tags = $storage->list();

        $this->assertCount(3, $tags);
        $this->assertContainsOnlyInstancesOf(TagEntity::class, $tags);
        // ORDER BY id ASC.
        $this->assertSame((string) $firstId, $tags[0]->tagId);
        $this->assertSame((string) $secondId, $tags[1]->tagId);
        $this->assertSame((string) $thirdId, $tags[2]->tagId);
        $this->assertSame('New Arrivals', $tags[0]->tagName);
        $this->assertSame('Sale', $tags[1]->tagName);
        $this->assertSame('Limited', $tags[2]->tagName);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = $this->sql(SqlTagStorage::class);
        $this->assertSame([], $storage->list());
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $id = $this->insertTag(['name' => '新商品']);

        $storage = $this->sql(SqlTagStorage::class);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(TagEntity::class, $entity);
        $this->assertSame((string) $id, $entity->tagId);
        $this->assertSame('新商品', $entity->tagName);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = $this->sql(SqlTagStorage::class);
        $this->assertNull($storage->getById('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // Fake-shaped seeds (`tg-new`, `tg-sale`) and hex ids from
        // FakeTagIdGenerator can never match an int PK; surface as
        // miss so TagDeleted's 404 path fires instead of a PDO error.
        $storage = $this->sql(SqlTagStorage::class);
        $this->assertNull($storage->getById('tg-new'));
        $this->assertNull($storage->getById('tg-deadbeefdeadbeef'));
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        $generator = $this->sql(TagIdGeneratorInterface::class);
        $newId = $generator->generate()->value(); // numeric string

        $entity = new TagEntity(tagId: $newId, tagName: '限定');

        $storage = $this->sql(SqlTagStorage::class);
        $storage->put($entity);

        $read = $storage->getById($newId);
        $this->assertInstanceOf(TagEntity::class, $read);
        $this->assertSame($newId, $read->tagId);
        $this->assertSame('限定', $read->tagName);

        // list() also sees it.
        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame($newId, $all[0]->tagId);
    }

    public function testPutSetsSortNoToZeroOnInsert(): void
    {
        $generator = $this->sql(TagIdGeneratorInterface::class);
        $newId = $generator->generate()->value();
        $storage = $this->sql(SqlTagStorage::class);

        $storage->put(new TagEntity(tagId: $newId, tagName: 'X'));

        // sort_no is NOT NULL in dtb_tag but absent from TagEntity;
        // we fix it to 0 on insert. Probe the underlying row.
        $stmt = $this->pdo->prepare('SELECT sort_no FROM dtb_tag WHERE id = :id');
        $stmt->execute([':id' => (int) $newId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(0, (int) $row['sort_no']);
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        $id = $this->insertTag(['name' => 'old name', 'sort_no' => 5]);

        $merged = new TagEntity(tagId: (string) $id, tagName: 'new name');

        $storage = $this->sql(SqlTagStorage::class);
        $storage->put($merged);

        $read = $storage->getById((string) $id);
        $this->assertInstanceOf(TagEntity::class, $read);
        $this->assertSame('new name', $read->tagName);

        // UPDATE leaves sort_no alone (we only touch the columns
        // TagEntity carries).
        $stmt = $this->pdo->prepare('SELECT sort_no FROM dtb_tag WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(5, (int) $row['sort_no']);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testPutIsNoOpForNonNumericIds(): void
    {
        $storage = $this->sql(SqlTagStorage::class);

        $storage->put(new TagEntity(tagId: 'tg-new', tagName: 'Fake-shaped id'));
        $storage->put(new TagEntity(tagId: 'tg-deadbeefdeadbeef', tagName: 'hex id'));

        $this->assertSame([], $storage->list());
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $id = $this->insertTag(['name' => 'doomed']);
        $storage = $this->sql(SqlTagStorage::class);
        $this->assertNotNull($storage->getById((string) $id));

        $storage->remove((string) $id);

        $this->assertNull($storage->getById((string) $id));
        $this->assertSame([], $storage->list());
    }

    public function testRemoveIsSilentNoOpForMissingId(): void
    {
        $storage = $this->sql(SqlTagStorage::class);
        $storage->remove('99999999'); // no row, no exception
        $storage->remove('tg-new'); // non-numeric, no exception
        $storage->remove('tg-deadbeefdeadbeef'); // hex, no exception
        $this->assertTrue(true);
    }

    public function testReorderRewritesSortNo(): void
    {
        $id = $this->insertTag(['name' => 'movable', 'sort_no' => 3]);
        $storage = $this->sql(SqlTagStorage::class);

        $storage->reorder((string) $id, 17);

        $stmt = $this->pdo->prepare('SELECT sort_no FROM dtb_tag WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(17, (int) $row['sort_no']);
        // The projection is untouched (sort_no is storage-only).
        $entity = $storage->getById((string) $id);
        $this->assertInstanceOf(TagEntity::class, $entity);
        $this->assertSame('movable', $entity->tagName);
    }

    public function testReorderIsSilentNoOpForNonNumericId(): void
    {
        $storage = $this->sql(SqlTagStorage::class);
        $storage->reorder('tg-new', 5); // non-numeric, no exception
        $this->assertTrue(true);
    }

    public function testTagIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = $this->sql(TagIdGeneratorInterface::class);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->generate()->value());

        $firstId = $this->insertTag();
        $secondId = $this->insertTag();
        $this->assertSame((string) ($secondId + 1), $generator->generate()->value());
        $this->assertGreaterThan($firstId, $secondId);
    }
}
