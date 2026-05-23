<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlAddressStorage;
use MyVendor\BeMart\Be\Reason\Service\SqlAddressIdGenerator;

/**
 * Storage-layer coverage for {@see SqlAddressStorage} (Phase 2b).
 *
 * Mirrors the shape of {@see SqlFavoriteStorageTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AddressBookResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation.
 */
final class SqlAddressStorageTest extends AbstractSqlTestCase
{
    public function testListByCustomerReturnsRowsInIdOrder(): void
    {
        $customerId = $this->insertCustomer();
        $firstId = $this->insertAddress([
            'customer_id' => $customerId,
            'name02' => 'First',
            'postal_code' => '1500001',
            'pref_id' => null,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
        ]);
        $secondId = $this->insertAddress([
            'customer_id' => $customerId,
            'name02' => 'Second',
            'postal_code' => '1600022',
            'addr01' => '新宿区',
            'addr02' => '新宿2-2-2',
        ]);

        $storage = $this->sql(SqlAddressStorage::class);
        $addresses = $storage->listByCustomer((string) $customerId);

        $this->assertCount(2, $addresses);
        $this->assertContainsOnlyInstancesOf(AddressEntity::class, $addresses);
        // ORDER BY id ASC.
        $this->assertSame((string) $firstId, $addresses[0]->addressId);
        $this->assertSame((string) $secondId, $addresses[1]->addressId);
        $this->assertSame('First', $addresses[0]->name02);
        $this->assertSame('Second', $addresses[1]->name02);
        $this->assertSame((string) $customerId, $addresses[0]->customerId);
        // pref_id NULL → coerced to 0.
        $this->assertSame(0, $addresses[0]->pref);
        $this->assertSame('神宮前1-1-1', $addresses[0]->addr02);
    }

    public function testListByCustomerIsolatesAcrossCustomers(): void
    {
        $alice = $this->insertCustomer();
        $bob = $this->insertCustomer();
        $this->insertAddress(['customer_id' => $alice, 'name02' => 'Alice-1']);
        $this->insertAddress(['customer_id' => $alice, 'name02' => 'Alice-2']);
        $this->insertAddress(['customer_id' => $bob, 'name02' => 'Bob-1']);

        $storage = $this->sql(SqlAddressStorage::class);

        $this->assertCount(2, $storage->listByCustomer((string) $alice));
        $this->assertCount(1, $storage->listByCustomer((string) $bob));
    }

    public function testListByCustomerReturnsEmptyWhenNoneOwned(): void
    {
        $customerId = $this->insertCustomer();
        $otherId = $this->insertCustomer();
        $this->insertAddress(['customer_id' => $otherId]);

        $storage = $this->sql(SqlAddressStorage::class);
        $this->assertSame([], $storage->listByCustomer((string) $customerId));
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $customerId = $this->insertCustomer();
        $id = $this->insertAddress([
            'customer_id' => $customerId,
            'name01' => '山田',
            'name02' => '太郎',
            'kana01' => 'ヤマダ',
            'kana02' => 'タロウ',
            'postal_code' => '1500002',
            'addr01' => '渋谷区',
            'addr02' => '渋谷3-3-3',
            'phone_number' => '0312345678',
        ]);

        $storage = $this->sql(SqlAddressStorage::class);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(AddressEntity::class, $entity);
        $this->assertSame((string) $id, $entity->addressId);
        $this->assertSame((string) $customerId, $entity->customerId);
        $this->assertSame('山田', $entity->name01);
        $this->assertSame('太郎', $entity->name02);
        $this->assertSame('ヤマダ', $entity->kana01);
        $this->assertSame('1500002', $entity->postalCode);
        $this->assertSame('渋谷3-3-3', $entity->addr02);
        $this->assertSame('0312345678', $entity->phoneNumber);
        // pref_id unset → prefName degrades to null (mtb_pref empty).
        $this->assertNull($entity->prefName);
    }

    /**
     * Phase 3 enrichment — listByCustomer / getById LEFT JOIN mtb_pref
     * and surface the prefecture display name as `prefName`, so the
     * address-book screen can render the name rather than the bare
     * integer master id.
     */
    public function testPrefNameResolvedFromMtbPrefJoin(): void
    {
        $customerId = $this->insertCustomer();
        $this->insertPref(13, '東京都');
        $id = $this->insertAddress([
            'customer_id' => $customerId,
            'name01' => '山田',
            'name02' => 'アリス',
            'pref_id' => 13,
        ]);

        $storage = $this->sql(SqlAddressStorage::class);

        $entity = $storage->getById((string) $id);
        $this->assertInstanceOf(AddressEntity::class, $entity);
        $this->assertSame(13, $entity->pref);
        $this->assertSame('東京都', $entity->prefName);

        $list = $storage->listByCustomer((string) $customerId);
        $this->assertCount(1, $list);
        $this->assertSame('東京都', $list[0]->prefName);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = $this->sql(SqlAddressStorage::class);
        $this->assertNull($storage->getById('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // A hex-shaped id (Fake convention) can never match an int PK;
        // surface as miss so the Final's 404 path fires instead of a
        // PDO error.
        $storage = $this->sql(SqlAddressStorage::class);
        $this->assertNull($storage->getById('deadbeefdeadbeefdeadbeefdeadbeef'));
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        $customerId = $this->insertCustomer();
        $this->insertPref(13, 'Tokyo'); // FK target — mtb_pref empty by default
        $generator = $this->sql(SqlAddressIdGenerator::class);
        $newId = $generator->generate(); // numeric string

        $entity = new AddressEntity(
            addressId: $newId,
            customerId: (string) $customerId,
            name01: '田中',
            name02: '花子',
            kana01: 'タナカ',
            kana02: 'ハナコ',
            companyName: null,
            phoneNumber: '0398765432',
            postalCode: '1700013',
            pref: 13,
            addr01: '豊島区',
            addr02: '東池袋4-4-4',
        );

        $storage = $this->sql(SqlAddressStorage::class);
        $storage->put($entity);

        $read = $storage->getById($newId);
        $this->assertInstanceOf(AddressEntity::class, $read);
        $this->assertSame($newId, $read->addressId);
        $this->assertSame((string) $customerId, $read->customerId);
        $this->assertSame('田中', $read->name01);
        $this->assertSame('花子', $read->name02);
        $this->assertSame('タナカ', $read->kana01);
        $this->assertSame(13, $read->pref);
        $this->assertSame('東池袋4-4-4', $read->addr02);

        // listByCustomer also sees it.
        $owned = $storage->listByCustomer((string) $customerId);
        $this->assertCount(1, $owned);
        $this->assertSame($newId, $owned[0]->addressId);
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        $customerId = $this->insertCustomer();
        $this->insertPref(13, 'Tokyo'); // FK target — mtb_pref empty by default
        $id = $this->insertAddress([
            'customer_id' => $customerId,
            'name01' => '田中',
            'name02' => '花子',
            'postal_code' => '1700013',
            'pref_id' => 13,
            'addr01' => '豊島区',
            'addr02' => '池袋1-1-1',
            'phone_number' => '0311111111',
        ]);

        $merged = new AddressEntity(
            addressId: (string) $id,
            customerId: (string) $customerId,
            name01: '田中',
            name02: '花子',
            kana01: null,
            kana02: null,
            companyName: null,
            phoneNumber: '0399998888', // changed
            postalCode: '1700013',
            pref: 13,
            addr01: '豊島区',
            addr02: '池袋1-1-1',
        );

        $storage = $this->sql(SqlAddressStorage::class);
        $storage->put($merged);

        $read = $storage->getById((string) $id);
        $this->assertInstanceOf(AddressEntity::class, $read);
        $this->assertSame('0399998888', $read->phoneNumber);
        // Unrelated fields preserved.
        $this->assertSame('池袋1-1-1', $read->addr02);
        $this->assertSame(13, $read->pref);

        // Row count for the customer unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->listByCustomer((string) $customerId));
    }

    public function testPutIsNoOpForNonNumericIds(): void
    {
        $customerId = $this->insertCustomer();
        $storage = $this->sql(SqlAddressStorage::class);

        $storage->put(new AddressEntity(
            addressId: 'deadbeefdeadbeefdeadbeefdeadbeef', // hex, not numeric
            customerId: (string) $customerId,
            name01: 'X',
            name02: 'Y',
            kana01: null,
            kana02: null,
            companyName: null,
            phoneNumber: null,
            postalCode: '',
            pref: 0,
            addr01: '',
            addr02: '',
        ));

        $this->assertSame([], $storage->listByCustomer((string) $customerId));
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $customerId = $this->insertCustomer();
        $id = $this->insertAddress(['customer_id' => $customerId]);
        $storage = $this->sql(SqlAddressStorage::class);
        $this->assertNotNull($storage->getById((string) $id));

        $storage->remove((string) $id);

        $this->assertNull($storage->getById((string) $id));
        $this->assertSame([], $storage->listByCustomer((string) $customerId));
    }

    public function testRemoveIsSilentNoOpForMissingId(): void
    {
        $storage = $this->sql(SqlAddressStorage::class);
        $storage->remove('99999999'); // no row, no exception
        $storage->remove('deadbeefdeadbeefdeadbeefdeadbeef'); // non-numeric, no exception
        $this->assertTrue(true);
    }

    public function testSqlAddressIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = $this->sql(SqlAddressIdGenerator::class);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->generate());

        $customerId = $this->insertCustomer();
        $firstId = $this->insertAddress(['customer_id' => $customerId]);
        $secondId = $this->insertAddress(['customer_id' => $customerId]);
        $this->assertSame((string) ($secondId + 1), $generator->generate());
        $this->assertGreaterThan($firstId, $secondId);
    }
}
