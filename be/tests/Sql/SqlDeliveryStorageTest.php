<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlDeliveryStorage;
use MyVendor\BeMart\Be\Reason\Service\SqlDeliveryIdGenerator;

/**
 * Storage-layer coverage for {@see SqlDeliveryStorage} (Phase 2b).
 *
 * Mirrors the shape of {@see SqlBlockStorageTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminDeliveryResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip /
 * non-numeric id rejection.
 */
final class SqlDeliveryStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $firstId = $this->insertDelivery(['name' => 'ヤマト宅急便']);
        $secondId = $this->insertDelivery(['name' => 'ゆうパック']);
        $thirdId = $this->insertDelivery(['name' => '佐川急便']);

        $storage = new SqlDeliveryStorage($this->pdo);
        $rows = $storage->list();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(DeliveryEntity::class, $rows);
        // ORDER BY id ASC.
        $this->assertSame((string) $firstId, $rows[0]->deliveryId);
        $this->assertSame((string) $secondId, $rows[1]->deliveryId);
        $this->assertSame((string) $thirdId, $rows[2]->deliveryId);
        $this->assertSame('ヤマト宅急便', $rows[0]->deliveryName);
        $this->assertSame('ゆうパック', $rows[1]->deliveryName);
        $this->assertSame('佐川急便', $rows[2]->deliveryName);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = new SqlDeliveryStorage($this->pdo);
        $this->assertSame([], $storage->list());
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $id = $this->insertDelivery([
            'name' => 'ヤマト宅急便',
            'visible' => 1,
        ]);

        $storage = new SqlDeliveryStorage($this->pdo);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(DeliveryEntity::class, $entity);
        $this->assertSame((string) $id, $entity->deliveryId);
        $this->assertSame('ヤマト宅急便', $entity->deliveryName);
        $this->assertTrue($entity->visible);
    }

    public function testGetByIdCoercesNullableNameToEmptyString(): void
    {
        // name is nullable in EC-CUBE but DeliveryEntity declares it
        // non-null. The hydrator coalesces NULL → '' so the projection
        // shape stays stable across externally-inserted rows.
        $id = $this->insertDelivery(['name' => null]);

        $storage = new SqlDeliveryStorage($this->pdo);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(DeliveryEntity::class, $entity);
        $this->assertSame('', $entity->deliveryName);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = new SqlDeliveryStorage($this->pdo);
        $this->assertNull($storage->getById('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // 32-char hex from FakeDeliveryIdGenerator can never match an
        // int PK; surface as miss so DeliveryUpdated / DeliveryDeleted
        // fire their 404 paths instead of a PDO error.
        $storage = new SqlDeliveryStorage($this->pdo);
        $this->assertNull($storage->getById('deadbeefdeadbeefdeadbeefdeadbeef'));
        $this->assertNull($storage->getById('nonexistent-zzz'));
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        $generator = new SqlDeliveryIdGenerator($this->pdo);
        $newId = $generator->generate(); // numeric string

        $entity = new DeliveryEntity(
            deliveryId: $newId,
            deliveryName: 'クロネコDM便',
            visible: true,
        );

        $storage = new SqlDeliveryStorage($this->pdo);
        $storage->put($entity);

        $read = $storage->getById($newId);
        $this->assertInstanceOf(DeliveryEntity::class, $read);
        $this->assertSame($newId, $read->deliveryId);
        $this->assertSame('クロネコDM便', $read->deliveryName);
        $this->assertTrue($read->visible);

        // list() also sees it.
        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame($newId, $all[0]->deliveryId);
    }

    public function testPutPersistsVisibleAsTinyint(): void
    {
        // A soft-hidden delivery method (visible=false) round-trips the
        // bool ↔ tinyint coercion.
        $generator = new SqlDeliveryIdGenerator($this->pdo);
        $newId = $generator->generate();
        $storage = new SqlDeliveryStorage($this->pdo);

        $storage->put(new DeliveryEntity(
            deliveryId: $newId,
            deliveryName: 'Hidden Method',
            visible: false,
        ));

        $read = $storage->getById($newId);
        $this->assertInstanceOf(DeliveryEntity::class, $read);
        $this->assertFalse($read->visible);

        // Raw column probe — the value is stored as the same tinyint.
        $stmt = $this->pdo->prepare(
            'SELECT visible FROM dtb_delivery WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(0, (int) $row['visible']);
    }

    public function testPutWritesNullSaleTypeId(): void
    {
        // DeliveryEntity carries no sale-type axis; the INSERT writes
        // sale_type_id = NULL so the FK to the (empty) mtb_sale_type
        // master never raises FK 1452.
        $generator = new SqlDeliveryIdGenerator($this->pdo);
        $newId = $generator->generate();
        $storage = new SqlDeliveryStorage($this->pdo);

        $storage->put(new DeliveryEntity(
            deliveryId: $newId,
            deliveryName: '通常配送',
            visible: true,
        ));

        $stmt = $this->pdo->prepare(
            'SELECT sale_type_id, creator_id FROM dtb_delivery WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertNull($row['sale_type_id']);
        $this->assertNull($row['creator_id']);
    }

    public function testPutIsNoOpForNonNumericId(): void
    {
        $storage = new SqlDeliveryStorage($this->pdo);

        $storage->put(new DeliveryEntity(
            deliveryId: 'deadbeefdeadbeefdeadbeefdeadbeef',
            deliveryName: 'Fake-shaped id',
            visible: true,
        ));

        $this->assertSame([], $storage->list());
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        // Insert via fixture (so id is known) then re-put via storage
        // with the same id — exercises the UPDATE branch. ALPS defines
        // doUpdateDelivery so the UPDATE path is driven by normal admin
        // flows (UpdateDeliveryInput / DeliveryUpdated).
        $id = $this->insertDelivery([
            'name' => 'ヤマト',
            'visible' => 1,
        ]);

        $merged = new DeliveryEntity(
            deliveryId: (string) $id,
            deliveryName: 'ヤマト宅急便',
            visible: false,
        );

        $storage = new SqlDeliveryStorage($this->pdo);
        $storage->put($merged);

        $read = $storage->getById((string) $id);
        $this->assertInstanceOf(DeliveryEntity::class, $read);
        $this->assertSame('ヤマト宅急便', $read->deliveryName);
        $this->assertFalse($read->visible);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $id = $this->insertDelivery(['name' => 'doomed']);
        $storage = new SqlDeliveryStorage($this->pdo);
        $this->assertNotNull($storage->getById((string) $id));

        $storage->remove((string) $id);

        $this->assertNull($storage->getById((string) $id));
        $this->assertSame([], $storage->list());
    }

    public function testRemoveIsSilentNoOpForMissingOrNonNumericId(): void
    {
        $storage = new SqlDeliveryStorage($this->pdo);
        $storage->remove('99999999'); // no row, no exception
        $storage->remove('deadbeefdeadbeefdeadbeefdeadbeef'); // hex, no exception
        $this->assertTrue(true);
    }

    public function testReorderRewritesSortNo(): void
    {
        $id = $this->insertDelivery(['sort_no' => 4]);
        $storage = new SqlDeliveryStorage($this->pdo);

        $storage->reorder((string) $id, 31);

        $stmt = $this->pdo->prepare('SELECT sort_no FROM dtb_delivery WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(31, (int) $row['sort_no']);
    }

    public function testSetVisibleRewritesVisibleColumnAndIsReadBack(): void
    {
        $id = $this->insertDelivery(['visible' => 1]);
        $storage = new SqlDeliveryStorage($this->pdo);

        $storage->setVisible((string) $id, false);

        // `visible` IS part of the DeliveryEntity projection.
        $entity = $storage->getById((string) $id);
        $this->assertInstanceOf(DeliveryEntity::class, $entity);
        $this->assertFalse($entity->visible);

        $storage->setVisible((string) $id, true);
        $back = $storage->getById((string) $id);
        $this->assertInstanceOf(DeliveryEntity::class, $back);
        $this->assertTrue($back->visible);
    }

    public function testReorderAndSetVisibleAreSilentNoOpForNonNumericId(): void
    {
        $storage = new SqlDeliveryStorage($this->pdo);
        $storage->reorder('deadbeefdeadbeefdeadbeefdeadbeef', 5);
        $storage->setVisible('deadbeefdeadbeefdeadbeefdeadbeef', false);
        $this->assertTrue(true);
    }

    public function testSqlDeliveryIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = new SqlDeliveryIdGenerator($this->pdo);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->generate());

        $firstId = $this->insertDelivery();
        $secondId = $this->insertDelivery();
        $this->assertSame((string) ($secondId + 1), $generator->generate());
        $this->assertGreaterThan($firstId, $secondId);
    }
}
