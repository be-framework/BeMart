<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\ShippingAddressEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlShippingAddressStorage;

use function array_map;

/**
 * Storage-layer coverage for {@see SqlShippingAddressStorage} (Phase 2b).
 *
 * Per G-23 the client-observable contract lives in the Resource-layer
 * sibling ({@see \MyVendor\BeMart\Tests\Resource\Sql\AdminOrderExtrasResourceSqlTest});
 * the cases below pin the per-method SQL paths in isolation.
 *
 * Surprises this suite locks in:
 *  - dtb_shipping references the order via the int FK `order_id`, so
 *    every method translates the customer-facing `orderNo` → dtb_order.id.
 *  - An orderNo with no dtb_order row is an honest miss: getByOrderNo
 *    returns null, put is a silent no-op.
 *  - `put` enforces single-row-per-order by probing `order_id` and
 *    UPDATEing in place — dtb_shipping has no UNIQUE on order_id.
 *  - pref_id is a nullable FK to the EMPTY mtb_pref master — pref=0
 *    writes NULL, NULL reads back as 0; a non-NULL pref needs insertPref.
 *  - postal_code / addr01 / addr02 / phone_number are column-nullable;
 *    NULL hydrates to '' (the Entity types them non-null).
 */
final class SqlShippingAddressStorageTest extends AbstractSqlTestCase
{
    /**
     * Build a ShippingAddressEntity with sensible defaults so each test
     * only states the fields it cares about.
     *
     * @param array<string, mixed> $overrides
     */
    private function entity(array $overrides = []): ShippingAddressEntity
    {
        $defaults = [
            'orderNo' => 'ORD-DEFAULT',
            'name01' => '山田',
            'name02' => '太郎',
            'postalCode' => '1500001',
            'pref' => 0,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
        ];
        $v = [...$defaults, ...$overrides];

        return new ShippingAddressEntity(
            orderNo: $v['orderNo'],
            name01: $v['name01'],
            name02: $v['name02'],
            postalCode: $v['postalCode'],
            pref: $v['pref'],
            addr01: $v['addr01'],
            addr02: $v['addr02'],
            phoneNumber: $v['phoneNumber'],
        );
    }

    public function testGetByOrderNoReturnsNullWhenOrderUnknown(): void
    {
        $storage = $this->sql(SqlShippingAddressStorage::class);
        $this->assertNull($storage->getByOrderNo('NO-SUCH-ORDER'));
    }

    public function testGetByOrderNoReturnsNullWhenOrderHasNoShippingRow(): void
    {
        // The order exists but no dtb_shipping row is attached.
        $order = $this->insertOrder(['order_no' => 'SHIP-GET-1']);

        $storage = $this->sql(SqlShippingAddressStorage::class);
        $this->assertNull($storage->getByOrderNo($order['orderNo']));
    }

    public function testGetByOrderNoReturnsHydratedEntity(): void
    {
        $this->insertPref(13, 'Tokyo'); // FK target — mtb_pref empty by default
        $order = $this->insertOrder(['order_no' => 'SHIP-GET-2']);
        $this->insertShipping([
            'order_id' => $order['id'],
            'name01' => '田中',
            'name02' => '花子',
            'postal_code' => '1700013',
            'pref_id' => 13,
            'addr01' => '豊島区',
            'addr02' => '東池袋4-4-4',
            'phone_number' => '0398765432',
        ]);

        $storage = $this->sql(SqlShippingAddressStorage::class);
        $entity = $storage->getByOrderNo($order['orderNo']);

        $this->assertInstanceOf(ShippingAddressEntity::class, $entity);
        $this->assertSame($order['orderNo'], $entity->orderNo);
        $this->assertSame('田中', $entity->name01);
        $this->assertSame('花子', $entity->name02);
        $this->assertSame('1700013', $entity->postalCode);
        $this->assertSame(13, $entity->pref);
        $this->assertSame('豊島区', $entity->addr01);
        $this->assertSame('東池袋4-4-4', $entity->addr02);
        $this->assertSame('0398765432', $entity->phoneNumber);
    }

    public function testGetByOrderNoCoercesNullColumnsToEntityDefaults(): void
    {
        // A dtb_shipping row with every nullable column left NULL —
        // postal_code / addr01 / addr02 / phone_number hydrate to '',
        // pref_id hydrates to 0.
        $order = $this->insertOrder(['order_no' => 'SHIP-NULL-1']);
        $this->insertShipping([
            'order_id' => $order['id'],
            'name01' => 'X',
            'name02' => 'Y',
            // postal_code / pref_id / addr01 / addr02 / phone_number
            // all default to NULL in the fixture helper.
        ]);

        $storage = $this->sql(SqlShippingAddressStorage::class);
        $entity = $storage->getByOrderNo($order['orderNo']);

        $this->assertInstanceOf(ShippingAddressEntity::class, $entity);
        $this->assertSame('', $entity->postalCode);
        $this->assertSame(0, $entity->pref);
        $this->assertSame('', $entity->addr01);
        $this->assertSame('', $entity->addr02);
        $this->assertSame('', $entity->phoneNumber);
    }

    public function testGetByOrderNoReturnsEarliestRowWhenMultiShip(): void
    {
        // dtb_shipping allows N rows per order; getByOrderNo returns the
        // earliest (ORDER BY id ASC LIMIT 1) — the simple-shipping case.
        $order = $this->insertOrder(['order_no' => 'SHIP-MULTI-1']);
        $this->insertShipping(['order_id' => $order['id'], 'name02' => 'First']);
        $this->insertShipping(['order_id' => $order['id'], 'name02' => 'Second']);

        $storage = $this->sql(SqlShippingAddressStorage::class);
        $entity = $storage->getByOrderNo($order['orderNo']);

        $this->assertInstanceOf(ShippingAddressEntity::class, $entity);
        $this->assertSame('First', $entity->name02);
    }

    public function testPutInsertsNewRowWhenOrderHasNoShipping(): void
    {
        $this->insertPref(13, 'Tokyo');
        $order = $this->insertOrder(['order_no' => 'SHIP-PUT-1']);

        $storage = $this->sql(SqlShippingAddressStorage::class);
        $storage->put($this->entity([
            'orderNo' => $order['orderNo'],
            'name01' => '佐藤',
            'name02' => '次郎',
            'postalCode' => '1600022',
            'pref' => 13,
            'addr01' => '新宿区',
            'addr02' => '新宿2-2-2',
            'phoneNumber' => '0399998888',
        ]));

        // Read-after-write round-trips through getByOrderNo.
        $read = $storage->getByOrderNo($order['orderNo']);
        $this->assertInstanceOf(ShippingAddressEntity::class, $read);
        $this->assertSame('佐藤', $read->name01);
        $this->assertSame('次郎', $read->name02);
        $this->assertSame('1600022', $read->postalCode);
        $this->assertSame(13, $read->pref);
        $this->assertSame('新宿区', $read->addr01);
        $this->assertSame('新宿2-2-2', $read->addr02);
        $this->assertSame('0399998888', $read->phoneNumber);

        // Exactly one dtb_shipping row was created.
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_shipping WHERE order_id = :id',
        );
        $stmt->execute([':id' => $order['id']]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        $order = $this->insertOrder(['order_no' => 'SHIP-PUT-2']);
        $shippingId = $this->insertShipping([
            'order_id' => $order['id'],
            'name01' => '山田',
            'name02' => '太郎',
            'postal_code' => '1500001',
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phone_number' => '0311111111',
        ]);

        $storage = $this->sql(SqlShippingAddressStorage::class);
        $storage->put($this->entity([
            'orderNo' => $order['orderNo'],
            'name01' => '田中',
            'name02' => '花子',
            'postalCode' => '1500002',
            'addr01' => '渋谷区',
            'addr02' => '神宮前9-9-9', // changed
            'phoneNumber' => '0399998888', // changed
        ]));

        $read = $storage->getByOrderNo($order['orderNo']);
        $this->assertInstanceOf(ShippingAddressEntity::class, $read);
        $this->assertSame('田中', $read->name01);
        $this->assertSame('神宮前9-9-9', $read->addr02);
        $this->assertSame('0399998888', $read->phoneNumber);

        // No duplicate row — the same physical row was UPDATEd.
        $stmt = $this->pdo->prepare(
            'SELECT id FROM dtb_shipping WHERE order_id = :id',
        );
        $stmt->execute([':id' => $order['id']]);
        $ids = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        $this->assertSame([$shippingId], $ids);
    }

    public function testPutIsNoOpWhenOrderUnknown(): void
    {
        // No dtb_order row → put silently no-ops (the Finals gate on
        // OrderQuery first; this is defence-in-depth).
        $storage = $this->sql(SqlShippingAddressStorage::class);
        $storage->put($this->entity(['orderNo' => 'NO-SUCH-ORDER']));

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM dtb_shipping');
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function testPutWritesNullPrefIdForZeroPref(): void
    {
        // pref=0 is the BeMart "no prefecture" sentinel; it must be
        // stored as a real NULL so the FK → empty mtb_pref holds.
        $order = $this->insertOrder(['order_no' => 'SHIP-PREF-0']);

        $storage = $this->sql(SqlShippingAddressStorage::class);
        $storage->put($this->entity(['orderNo' => $order['orderNo'], 'pref' => 0]));

        $stmt = $this->pdo->prepare(
            'SELECT pref_id FROM dtb_shipping WHERE order_id = :id',
        );
        $stmt->execute([':id' => $order['id']]);
        $this->assertNull($stmt->fetchColumn());

        // And it reads back as 0.
        $read = $storage->getByOrderNo($order['orderNo']);
        $this->assertInstanceOf(ShippingAddressEntity::class, $read);
        $this->assertSame(0, $read->pref);
    }

    public function testPutThenGetRoundTripsAcrossUpdate(): void
    {
        // INSERT via put, then UPDATE via put — getByOrderNo reflects the
        // second write.
        $order = $this->insertOrder(['order_no' => 'SHIP-RT-1']);
        $storage = $this->sql(SqlShippingAddressStorage::class);

        $storage->put($this->entity(['orderNo' => $order['orderNo'], 'name02' => 'V1']));
        $storage->put($this->entity(['orderNo' => $order['orderNo'], 'name02' => 'V2']));

        $read = $storage->getByOrderNo($order['orderNo']);
        $this->assertInstanceOf(ShippingAddressEntity::class, $read);
        $this->assertSame('V2', $read->name02);
    }

    public function testListAllReturnsEmptyWhenNoRows(): void
    {
        $storage = $this->sql(SqlShippingAddressStorage::class);
        $this->assertSame([], $storage->listAll());
    }

    public function testListAllReturnsEveryRowWithOrderNo(): void
    {
        $orderA = $this->insertOrder(['order_no' => 'SHIP-LIST-A']);
        $orderB = $this->insertOrder(['order_no' => 'SHIP-LIST-B']);
        $this->insertShipping(['order_id' => $orderA['id'], 'name02' => 'A-ship']);
        $this->insertShipping(['order_id' => $orderB['id'], 'name02' => 'B-ship']);

        $storage = $this->sql(SqlShippingAddressStorage::class);
        $rows = $storage->listAll();

        $this->assertCount(2, $rows);
        $this->assertContainsOnlyInstancesOf(ShippingAddressEntity::class, $rows);
        // ORDER BY s.id ASC — insertion order.
        $this->assertSame($orderA['orderNo'], $rows[0]->orderNo);
        $this->assertSame('A-ship', $rows[0]->name02);
        $this->assertSame($orderB['orderNo'], $rows[1]->orderNo);
        $this->assertSame('B-ship', $rows[1]->name02);
    }

    public function testListAllReflectsPutWrites(): void
    {
        $order = $this->insertOrder(['order_no' => 'SHIP-LIST-PUT']);
        $storage = $this->sql(SqlShippingAddressStorage::class);

        $this->assertSame([], $storage->listAll());

        $storage->put($this->entity(['orderNo' => $order['orderNo'], 'name02' => 'Listed']));

        $rows = $storage->listAll();
        $this->assertCount(1, $rows);
        $this->assertSame($order['orderNo'], $rows[0]->orderNo);
        $this->assertSame('Listed', $rows[0]->name02);
    }

    public function testUpdateTrackingNumberUpdatesExistingShippingRow(): void
    {
        $order = $this->insertOrder(['order_no' => 'SHIP-TRACK-UPD']);
        $storage = $this->sql(SqlShippingAddressStorage::class);
        // A shipping row already exists for the order.
        $storage->put($this->entity(['orderNo' => $order['orderNo']]));

        $storage->updateTrackingNumber($order['orderNo'], 'TRK-12345');

        $this->assertSame('TRK-12345', $storage->trackingNumberByOrderNo($order['orderNo']));
        // The address fields are untouched by the tracking write.
        $address = $storage->getByOrderNo($order['orderNo']);
        $this->assertNotNull($address);
        $this->assertSame('山田', $address->name01);
    }

    public function testUpdateTrackingNumberInsertsMinimalRowWhenNoneExists(): void
    {
        $order = $this->insertOrder(['order_no' => 'SHIP-TRACK-INS']);
        $storage = $this->sql(SqlShippingAddressStorage::class);
        $this->assertNull($storage->trackingNumberByOrderNo($order['orderNo']));

        $storage->updateTrackingNumber($order['orderNo'], 'TRK-99999');

        $this->assertSame('TRK-99999', $storage->trackingNumberByOrderNo($order['orderNo']));
    }

    public function testUpdateTrackingNumberIsSilentNoOpForUnknownOrder(): void
    {
        $storage = $this->sql(SqlShippingAddressStorage::class);
        $storage->updateTrackingNumber('NO-SUCH-ORDER', 'TRK-1'); // no exception
        $this->assertNull($storage->trackingNumberByOrderNo('NO-SUCH-ORDER'));
    }

    public function testTrackingNumberByOrderNoIsNullWhenUnset(): void
    {
        $order = $this->insertOrder(['order_no' => 'SHIP-TRACK-NULL']);
        $storage = $this->sql(SqlShippingAddressStorage::class);
        // A shipping row exists but its tracking_number column is NULL.
        $storage->put($this->entity(['orderNo' => $order['orderNo']]));

        $this->assertNull($storage->trackingNumberByOrderNo($order['orderNo']));
    }
}
