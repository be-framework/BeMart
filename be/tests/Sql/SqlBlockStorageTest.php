<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\BlockEntity;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\BlockIdGeneratorInterface;

use function date;

/**
 * Storage-layer coverage for {@see BlockStorageInterface} (Phase 2b).
 *
 * Mirrors the shape of {@see PageStorageInterfaceTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminBlockResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip /
 * dtb_block_position cascade on remove.
 */
final class SqlBlockStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $firstId = $this->insertBlock(['block_name' => 'Header']);
        $secondId = $this->insertBlock(['block_name' => 'Footer']);
        $thirdId = $this->insertBlock(['block_name' => 'Sidebar']);

        $storage = $this->sql(BlockStorageInterface::class);
        $rows = $storage->list();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(BlockEntity::class, $rows);
        // ORDER BY id ASC.
        $this->assertSame((string) $firstId, $rows[0]->blockId);
        $this->assertSame((string) $secondId, $rows[1]->blockId);
        $this->assertSame((string) $thirdId, $rows[2]->blockId);
        $this->assertSame('Header', $rows[0]->blockName);
        $this->assertSame('Footer', $rows[1]->blockName);
        $this->assertSame('Sidebar', $rows[2]->blockName);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = $this->sql(BlockStorageInterface::class);
        $this->assertSame([], $storage->list());
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $id = $this->insertBlock([
            'block_name' => 'ヘッダー',
            'file_name' => 'header',
            'deletable' => 0,
        ]);

        $storage = $this->sql(BlockStorageInterface::class);
        $entity = $storage->item((string) $id);

        $this->assertInstanceOf(BlockEntity::class, $entity);
        $this->assertSame((string) $id, $entity->blockId);
        $this->assertSame('ヘッダー', $entity->blockName);
        $this->assertSame('header', $entity->blockFileName);
        $this->assertFalse($entity->blockDeletable);
    }

    public function testGetByIdCoercesNullableBlockNameToEmptyString(): void
    {
        // block_name is nullable in EC-CUBE but BlockEntity declares it
        // non-null. The hydrator coalesces NULL → '' so the projection
        // shape stays stable across externally-inserted rows.
        $id = $this->insertBlock([
            'block_name' => null,
            'file_name' => 'bare',
        ]);

        $storage = $this->sql(BlockStorageInterface::class);
        $entity = $storage->item((string) $id);

        $this->assertInstanceOf(BlockEntity::class, $entity);
        $this->assertSame('', $entity->blockName);
        $this->assertSame('bare', $entity->blockFileName);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = $this->sql(BlockStorageInterface::class);
        $this->assertNull($storage->item('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // The Fake seed `bk-header` and hex ids from FakeBlockIdGenerator
        // can never match an int PK; surface as miss so BlockDeleted /
        // BlockUpdated fire their 404 paths instead of a PDO error.
        $storage = $this->sql(BlockStorageInterface::class);
        $this->assertNull($storage->item('bk-header'));
        $this->assertNull($storage->item('bk-deadbeefdeadbeef'));
        $this->assertNull($storage->item('nonexistent-zzz'));
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        $generator = $this->sql(BlockIdGeneratorInterface::class);
        $newId = $generator->next()->value; // numeric string

        $entity = new BlockEntity(
            blockId: $newId,
            blockName: 'バナー',
            blockFileName: 'banner',
            blockDeletable: true,
        );

        $storage = $this->sql(BlockStorageInterface::class);
        $storage->put($entity);

        $read = $storage->item($newId);
        $this->assertInstanceOf(BlockEntity::class, $read);
        $this->assertSame($newId, $read->blockId);
        $this->assertSame('バナー', $read->blockName);
        $this->assertSame('banner', $read->blockFileName);
        $this->assertTrue($read->blockDeletable);

        // list() also sees it.
        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame($newId, $all[0]->blockId);
    }

    public function testPutPersistsDeletableAsTinyint(): void
    {
        // System blocks (blockDeletable=false) round-trip the same as
        // user blocks — only BlockDeleted enforces the guard, not the
        // storage.
        $generator = $this->sql(BlockIdGeneratorInterface::class);
        $newId = $generator->next()->value;
        $storage = $this->sql(BlockStorageInterface::class);

        $storage->put(new BlockEntity(
            blockId: $newId,
            blockName: 'System Header',
            blockFileName: 'system_header',
            blockDeletable: false,
        ));

        $read = $storage->item($newId);
        $this->assertInstanceOf(BlockEntity::class, $read);
        $this->assertFalse($read->blockDeletable);

        // Raw column probe — the value is stored as the same tinyint.
        $stmt = $this->pdo->prepare(
            'SELECT deletable FROM dtb_block WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(0, (int) $row['deletable']);
    }

    public function testPutIsNoOpForNonNumericIds(): void
    {
        $storage = $this->sql(BlockStorageInterface::class);

        $storage->put(new BlockEntity(
            blockId: 'bk-header',
            blockName: 'Fake-shaped id',
            blockFileName: 'header',
            blockDeletable: false,
        ));
        $storage->put(new BlockEntity(
            blockId: 'bk-deadbeefdeadbeef',
            blockName: 'hex id',
            blockFileName: 'hex',
            blockDeletable: true,
        ));

        $this->assertSame([], $storage->list());
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        // Insert via fixture (so id is known) then re-put via storage
        // with the same id — exercises the UPDATE branch. ALPS defines
        // doUpdateBlock so the UPDATE path is driven by normal admin
        // flows (UpdateBlockInput / BlockUpdated).
        $id = $this->insertBlock([
            'block_name' => 'Old',
            'file_name' => 'old_file',
            'deletable' => 1,
        ]);

        $merged = new BlockEntity(
            blockId: (string) $id,
            blockName: 'New',
            blockFileName: 'new_file',
            blockDeletable: true,
        );

        $storage = $this->sql(BlockStorageInterface::class);
        $storage->put($merged);

        $read = $storage->item((string) $id);
        $this->assertInstanceOf(BlockEntity::class, $read);
        $this->assertSame('New', $read->blockName);
        $this->assertSame('new_file', $read->blockFileName);
        $this->assertTrue($read->blockDeletable);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $id = $this->insertBlock(['block_name' => 'doomed']);
        $storage = $this->sql(BlockStorageInterface::class);
        $this->assertNotNull($storage->item((string) $id));

        $storage->delete((string) $id);

        $this->assertNull($storage->item((string) $id));
        $this->assertSame([], $storage->list());
    }

    public function testRemoveCascadesDtbBlockPositionPlacements(): void
    {
        // dtb_block_position's FK (block_id → dtb_block.id) would
        // otherwise raise FK 1451 on the block DELETE. BlockStorageInterface::remove
        // pre-DELETEs the placement rows so the block-level delete
        // succeeds regardless of placement state.
        $id = $this->insertBlock(['block_name' => 'placed']);

        // Seed a placement row directly. dtb_block_position FKs both
        // block_id (→ dtb_block.id) and layout_id (→ dtb_layout.id);
        // the structure-only dump leaves dtb_layout empty, so seed a
        // parent layout too. dtb_layout NOT NULL: create_date,
        // update_date, discriminator_type (id auto / layout_name
        // nullable).
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare(
            'INSERT INTO dtb_layout '
            . '(id, layout_name, create_date, update_date, discriminator_type) '
            . 'VALUES (:id, :name, :created, :updated, :discriminator)',
        )->execute([
            ':id' => 1,
            ':name' => 'PC',
            ':created' => $now,
            ':updated' => $now,
            ':discriminator' => 'layout',
        ]);
        $this->pdo->prepare(
            'INSERT INTO dtb_block_position '
            . '(section, block_id, layout_id, block_row, discriminator_type) '
            . 'VALUES (:section, :block_id, :layout_id, :block_row, :discriminator)',
        )->execute([
            ':section' => 1,
            ':block_id' => $id,
            ':layout_id' => 1,
            ':block_row' => 0,
            ':discriminator' => 'blockposition',
        ]);

        $storage = $this->sql(BlockStorageInterface::class);
        $storage->delete((string) $id);

        // Block is gone.
        $this->assertNull($storage->item((string) $id));

        // Placement row is also gone (cleanup, not just FK satisfaction).
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_block_position WHERE block_id = :id',
        );
        $stmt->execute([':id' => $id]);
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function testRemoveIsSilentNoOpForMissingId(): void
    {
        $storage = $this->sql(BlockStorageInterface::class);
        $storage->delete('99999999'); // no row, no exception
        $storage->delete('bk-header'); // non-numeric, no exception
        $storage->delete('bk-deadbeefdeadbeef'); // hex, no exception
        $this->assertTrue(true);
    }

    public function testBlockIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = $this->sql(BlockIdGeneratorInterface::class);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->next()->value);

        $firstId = $this->insertBlock();
        $secondId = $this->insertBlock();
        $this->assertSame((string) ($secondId + 1), $generator->next()->value);
        $this->assertGreaterThan($firstId, $secondId);
    }
}
