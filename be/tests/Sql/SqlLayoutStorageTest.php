<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\LayoutEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlLayoutStorage;

/**
 * Storage-layer coverage for {@see SqlLayoutStorage} (Phase 2b).
 *
 * Per G-23 the client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminLayoutResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip,
 * plus the structural concerns specific to dtb_layout: the nullable
 * device_type_id FK to mtb_device_type (10=PC, 2=Mobile), the NULL →
 * '' / NULL → 0 hydrator coercions, and that `put`'s UPDATE branch
 * (the only live write path — no Layout create affordance) leaves
 * device_type_id untouched.
 */
final class SqlLayoutStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $this->seedDeviceTypes();
        $first = $this->insertLayout(['layout_name' => 'PC標準', 'device_type_id' => 10]);
        $second = $this->insertLayout(['layout_name' => 'スマホ標準', 'device_type_id' => 2]);

        $storage = new SqlLayoutStorage($this->pdo);
        $rows = $storage->list();

        $this->assertCount(2, $rows);
        $this->assertContainsOnlyInstancesOf(LayoutEntity::class, $rows);
        $this->assertSame((string) $first, $rows[0]->layoutId);
        $this->assertSame((string) $second, $rows[1]->layoutId);
        $this->assertSame('PC標準', $rows[0]->layoutName);
        $this->assertSame('スマホ標準', $rows[1]->layoutName);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = new SqlLayoutStorage($this->pdo);
        $this->assertSame([], $storage->list());
    }

    public function testListProjectsDeviceTypeEnum(): void
    {
        // deviceType mirrors EC-CUBE's mtb_device_type enum: 10=PC,
        // 2=Mobile. The fixture seeds the master rows and writes a
        // non-NULL device_type_id; the projection round-trips it.
        $this->seedDeviceTypes();
        $this->insertLayout(['device_type_id' => 10]);
        $this->insertLayout(['device_type_id' => 2]);

        $storage = new SqlLayoutStorage($this->pdo);
        $rows = $storage->list();

        $this->assertSame(10, $rows[0]->deviceType);
        $this->assertSame(2, $rows[1]->deviceType);
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $this->seedDeviceTypes();
        $id = $this->insertLayout(['layout_name' => 'PC標準', 'device_type_id' => 10]);

        $storage = new SqlLayoutStorage($this->pdo);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(LayoutEntity::class, $entity);
        $this->assertSame((string) $id, $entity->layoutId);
        $this->assertSame('PC標準', $entity->layoutName);
        $this->assertSame(10, $entity->deviceType);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = new SqlLayoutStorage($this->pdo);
        $this->assertNull($storage->getById('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // The `lo-` prefixed seed handles the Fake emits (`lo-pc-default`
        // / `lo-sp-default`) and `nonexistent` can never match an int
        // PK; surface as miss so the LayoutUpdated Final fires its 404
        // path instead of a PDO error.
        $storage = new SqlLayoutStorage($this->pdo);
        $this->assertNull($storage->getById('lo-pc-default'));
        $this->assertNull($storage->getById('nonexistent'));
    }

    public function testGetByIdCoercesNullLayoutNameToEmptyString(): void
    {
        // layout_name is nullable in EC-CUBE but non-null on
        // LayoutEntity — an externally-inserted row with NULL must
        // still project a string.
        $this->seedDeviceTypes();
        $id = $this->insertLayout(['layout_name' => null]);

        $storage = new SqlLayoutStorage($this->pdo);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(LayoutEntity::class, $entity);
        $this->assertSame('', $entity->layoutName);
    }

    public function testGetByIdCoercesNullDeviceTypeToZero(): void
    {
        // device_type_id is nullable; LayoutEntity::deviceType is a
        // non-null int — a row with NULL projects deviceType = 0.
        $id = $this->insertLayout(['device_type_id' => null]);

        $storage = new SqlLayoutStorage($this->pdo);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(LayoutEntity::class, $entity);
        $this->assertSame(0, $entity->deviceType);
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        // Insert via fixture (so id is known) then re-put via storage
        // with the same id — exercises the UPDATE branch driven by the
        // normal admin rename flow (doUpdateLayout). This is the only
        // live write path: Layout has no create affordance.
        $this->seedDeviceTypes();
        $id = $this->insertLayout(['layout_name' => 'PC標準', 'device_type_id' => 10]);

        $merged = new LayoutEntity(
            layoutId: (string) $id,
            layoutName: 'PC Refreshed',
            deviceType: 10,
        );

        $storage = new SqlLayoutStorage($this->pdo);
        $storage->put($merged);

        $read = $storage->getById((string) $id);
        $this->assertInstanceOf(LayoutEntity::class, $read);
        $this->assertSame('PC Refreshed', $read->layoutName);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testPutUpdateLeavesDeviceTypeUntouched(): void
    {
        // A rename via UPDATE must not disturb device_type_id — a
        // layout's device class is fixed at install time. Probe the
        // raw column directly.
        $this->seedDeviceTypes();
        $id = $this->insertLayout(['layout_name' => 'PC標準', 'device_type_id' => 10]);

        $storage = new SqlLayoutStorage($this->pdo);
        $storage->put(new LayoutEntity(
            layoutId: (string) $id,
            layoutName: 'PC Refreshed',
            // Even if a caller passed a different deviceType, the
            // UPDATE branch never writes the column.
            deviceType: 2,
        ));

        $stmt = $this->pdo->prepare(
            'SELECT device_type_id FROM dtb_layout WHERE id = :id',
        );
        $stmt->execute([':id' => $id]);
        $this->assertSame(10, (int) $stmt->fetchColumn());
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        // The INSERT branch is defensive-only (no Layout create
        // affordance), but still exercised: a put against an id with no
        // existing row writes a fresh layout. device_type_id is NULL
        // (mtb_device_type FK guard), so the projection reads back
        // deviceType = 0.
        $storage = new SqlLayoutStorage($this->pdo);
        $storage->put(new LayoutEntity(
            layoutId: '777',
            layoutName: 'Fresh Layout',
            deviceType: 10,
        ));

        $read = $storage->getById('777');
        $this->assertInstanceOf(LayoutEntity::class, $read);
        $this->assertSame('777', $read->layoutId);
        $this->assertSame('Fresh Layout', $read->layoutName);
        $this->assertSame(0, $read->deviceType);

        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame('777', $all[0]->layoutId);
    }

    public function testPutIsNoOpForNonNumericId(): void
    {
        // The Fake seeds `lo-` prefixed string ids — the SQL impl
        // cannot persist a non-numeric PK, so put silently no-ops
        // rather than raising.
        $storage = new SqlLayoutStorage($this->pdo);
        $storage->put(new LayoutEntity(
            layoutId: 'lo-pc-default',
            layoutName: 'Fake-shaped id',
            deviceType: 10,
        ));

        $this->assertSame([], $storage->list());
    }

    public function testPutUpdateRoundTripsThroughList(): void
    {
        // After an UPDATE the renamed layout is visible in list() with
        // the new name and the original device type.
        $this->seedDeviceTypes();
        $pcId = $this->insertLayout(['layout_name' => 'PC標準', 'device_type_id' => 10]);
        $spId = $this->insertLayout(['layout_name' => 'スマホ標準', 'device_type_id' => 2]);

        $storage = new SqlLayoutStorage($this->pdo);
        $storage->put(new LayoutEntity(
            layoutId: (string) $pcId,
            layoutName: 'PC Refreshed',
            deviceType: 10,
        ));

        $rows = $storage->list();
        $this->assertCount(2, $rows);
        $this->assertSame((string) $pcId, $rows[0]->layoutId);
        $this->assertSame('PC Refreshed', $rows[0]->layoutName);
        $this->assertSame(10, $rows[0]->deviceType);
        // Sibling untouched.
        $this->assertSame((string) $spId, $rows[1]->layoutId);
        $this->assertSame('スマホ標準', $rows[1]->layoutName);
        $this->assertSame(2, $rows[1]->deviceType);
    }
}
