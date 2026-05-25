<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\BaseInfoEntity;
use MyVendor\BeMart\Be\Reason\Query\BaseInfoStorageInterface;

/**
 * Storage-layer coverage for {@see BaseInfoStorageInterface} (Phase 2b).
 *
 * Mirrors the shape of {@see TagStorageInterfaceTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminBaseInfoResourceSqlTest}
 * + {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminBaseInfoGetResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation:
 *
 *   - get() on an empty table → installer-default Entity (matching
 *     FakeBaseInfoStorage's constructor seeds, so both backends
 *     produce the IDENTICAL projection on a first read).
 *   - get() on a seeded id=1 row → hydrated Entity with the row's
 *     values, NULL columns coerced to null Entity fields.
 *   - update() on an empty table → INSERT id=1 with the supplied
 *     12 fields; round-trips via get().
 *   - update() on an existing id=1 → UPDATE in place; round-trips
 *     via get(); the non-Entity columns (option_*, point rates) are
 *     left at their schema-default values.
 *   - The singleton-row contract — repeated update() never produces
 *     a second row.
 */
final class SqlBaseInfoStorageTest extends AbstractSqlTestCase
{
    public function testGetReturnsInstallerDefaultsWhenRowMissing(): void
    {
        $storage = $this->sql(BaseInfoStorageInterface::class);
        $entity = $storage->get();

        // Mirrors FakeBaseInfoStorage's constructor seeds — the same
        // contract both backends honour for a first read.
        $this->assertSame('EC-CUBE SHOP', $entity->shopName);
        $this->assertSame('イーシーキューブショップ', $entity->shopKana);
        $this->assertSame('EC-CUBE SHOP', $entity->shopNameEng);
        $this->assertSame('株式会社EC-CUBE', $entity->companyName);
        $this->assertSame('5300001', $entity->postalCode);
        $this->assertSame(27, $entity->pref);
        $this->assertSame('大阪市北区', $entity->addr01);
        $this->assertSame('梅田1-1-1', $entity->addr02);
        $this->assertSame('0612345678', $entity->phoneNumber);
        $this->assertSame('10:00-19:00', $entity->businessHour);
        $this->assertSame('shop@example.com', $entity->shopEmail01);
        $this->assertSame('ようこそ、EC-CUBE SHOP へ。', $entity->shopMessage);
    }

    public function testGetHydratesSeededRow(): void
    {
        $this->insertBaseInfo([
            'shop_name' => '新ショップ',
            'shop_kana' => 'シンショップ',
            'shop_name_eng' => 'New Shop',
            'company_name' => '株式会社新',
            'postal_code' => '1500001',
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phone_number' => '0312345678',
            'business_hour' => '11:00-20:00',
            'email01' => 'newshop@example.com',
            'message' => 'いらっしゃいませ',
            // pref_id left NULL — FK to mtb_pref is unsatisfiable in
            // the structure-only dump unless we seed it; the storage
            // contract permits NULL.
        ]);

        $storage = $this->sql(BaseInfoStorageInterface::class);
        $entity = $storage->get();

        $this->assertSame('新ショップ', $entity->shopName);
        $this->assertSame('シンショップ', $entity->shopKana);
        $this->assertSame('New Shop', $entity->shopNameEng);
        $this->assertSame('株式会社新', $entity->companyName);
        $this->assertSame('1500001', $entity->postalCode);
        $this->assertNull($entity->pref);
        $this->assertSame('渋谷区', $entity->addr01);
        $this->assertSame('神宮前1-1-1', $entity->addr02);
        $this->assertSame('0312345678', $entity->phoneNumber);
        $this->assertSame('11:00-20:00', $entity->businessHour);
        $this->assertSame('newshop@example.com', $entity->shopEmail01);
        $this->assertSame('いらっしゃいませ', $entity->shopMessage);
    }

    public function testGetCoercesNullColumnsToNullableEntityFields(): void
    {
        // Almost every column NULL — only shop_name supplied to satisfy
        // the BaseInfoEntity::shopName non-null type.
        $this->insertBaseInfo(['shop_name' => 'Bare Shop']);

        $storage = $this->sql(BaseInfoStorageInterface::class);
        $entity = $storage->get();

        $this->assertSame('Bare Shop', $entity->shopName);
        $this->assertNull($entity->shopKana);
        $this->assertNull($entity->shopNameEng);
        $this->assertNull($entity->companyName);
        $this->assertNull($entity->postalCode);
        $this->assertNull($entity->pref);
        $this->assertNull($entity->addr01);
        $this->assertNull($entity->addr02);
        $this->assertNull($entity->phoneNumber);
        $this->assertNull($entity->businessHour);
        $this->assertNull($entity->shopEmail01);
        $this->assertNull($entity->shopMessage);
    }

    public function testGetFallsBackToDefaultShopNameWhenColumnIsNull(): void
    {
        // The Entity types shopName as non-null `string`, so the SQL
        // hydrator MUST produce a value even if the column happens to
        // be NULL. Mirror the installer default — same string the
        // Fake backend emits.
        $this->insertBaseInfo(['shop_name' => null]);

        $storage = $this->sql(BaseInfoStorageInterface::class);
        $entity = $storage->get();

        $this->assertSame('EC-CUBE SHOP', $entity->shopName);
    }

    public function testUpdateInsertsRowWhenTableIsEmpty(): void
    {
        $storage = $this->sql(BaseInfoStorageInterface::class);

        $entity = new BaseInfoEntity(
            shopName: '新ショップ',
            shopKana: 'シンショップ',
            shopNameEng: 'New Shop',
            companyName: '株式会社新',
            postalCode: '1500001',
            pref: null,
            addr01: '渋谷区',
            addr02: '神宮前1-1-1',
            phoneNumber: '0312345678',
            businessHour: '11:00-20:00',
            shopEmail01: 'newshop@example.com',
            shopMessage: 'いらっしゃいませ',
        );

        $storage->update($entity);

        $read = $storage->get();
        $this->assertSame('新ショップ', $read->shopName);
        $this->assertSame('シンショップ', $read->shopKana);
        $this->assertSame('New Shop', $read->shopNameEng);
        $this->assertSame('株式会社新', $read->companyName);
        $this->assertSame('1500001', $read->postalCode);
        $this->assertNull($read->pref);
        $this->assertSame('渋谷区', $read->addr01);
        $this->assertSame('神宮前1-1-1', $read->addr02);
        $this->assertSame('0312345678', $read->phoneNumber);
        $this->assertSame('11:00-20:00', $read->businessHour);
        $this->assertSame('newshop@example.com', $read->shopEmail01);
        $this->assertSame('いらっしゃいませ', $read->shopMessage);
    }

    public function testUpdateRewritesExistingRowInPlace(): void
    {
        $this->insertBaseInfo([
            'shop_name' => 'Old',
            'shop_kana' => 'オールド',
            'company_name' => '旧会社',
            'phone_number' => '0000000000',
        ]);

        $storage = $this->sql(BaseInfoStorageInterface::class);

        $entity = new BaseInfoEntity(
            shopName: 'New',
            shopKana: 'ニュー',
            shopNameEng: null,
            companyName: '新会社',
            postalCode: null,
            pref: null,
            addr01: null,
            addr02: null,
            phoneNumber: '0312345678',
            businessHour: null,
            shopEmail01: null,
            shopMessage: null,
        );

        $storage->update($entity);

        $read = $storage->get();
        $this->assertSame('New', $read->shopName);
        $this->assertSame('ニュー', $read->shopKana);
        $this->assertSame('新会社', $read->companyName);
        $this->assertSame('0312345678', $read->phoneNumber);
        // Cleared fields → null.
        $this->assertNull($read->shopNameEng);
        $this->assertNull($read->businessHour);
    }

    public function testUpdateRepeatedDoesNotCreateSecondRow(): void
    {
        $storage = $this->sql(BaseInfoStorageInterface::class);

        $entity = new BaseInfoEntity(
            shopName: 'Shop',
            shopKana: null,
            shopNameEng: null,
            companyName: null,
            postalCode: null,
            pref: null,
            addr01: null,
            addr02: null,
            phoneNumber: null,
            businessHour: null,
            shopEmail01: null,
            shopMessage: null,
        );

        // First call inserts.
        $storage->update($entity);
        // Subsequent calls should UPDATE, never INSERT.
        $storage->update($entity);
        $storage->update($entity);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM dtb_base_info');
        $this->assertNotFalse($stmt);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testUpdatePreservesNonEntityColumnsOnRewrite(): void
    {
        // Seed with non-default values in columns the Entity does not
        // carry (option_point, basic_point_rate) — the storage UPDATE
        // path must not touch them.
        $this->insertBaseInfo([
            'shop_name' => 'Seed',
        ]);

        // Verify the seed picked up the schema DEFAULTs.
        $beforeStmt = $this->pdo->query(
            'SELECT option_point, basic_point_rate, '
            . 'option_mypage_order_status_display, option_customer_activate '
            . 'FROM dtb_base_info WHERE id = 1',
        );
        $this->assertNotFalse($beforeStmt);
        $before = $beforeStmt->fetch();
        $this->assertNotFalse($before);

        $storage = $this->sql(BaseInfoStorageInterface::class);
        $storage->update(new BaseInfoEntity(
            shopName: 'Touched',
            shopKana: null,
            shopNameEng: null,
            companyName: null,
            postalCode: null,
            pref: null,
            addr01: null,
            addr02: null,
            phoneNumber: null,
            businessHour: null,
            shopEmail01: null,
            shopMessage: null,
        ));

        $afterStmt = $this->pdo->query(
            'SELECT option_point, basic_point_rate, '
            . 'option_mypage_order_status_display, option_customer_activate '
            . 'FROM dtb_base_info WHERE id = 1',
        );
        $this->assertNotFalse($afterStmt);
        $after = $afterStmt->fetch();
        $this->assertNotFalse($after);

        // Phase 2 scope columns untouched by the Wave 8/9 update.
        $this->assertSame((int) $before['option_point'], (int) $after['option_point']);
        $this->assertSame((string) $before['basic_point_rate'], (string) $after['basic_point_rate']);
        $this->assertSame(
            (int) $before['option_mypage_order_status_display'],
            (int) $after['option_mypage_order_status_display'],
        );
        $this->assertSame(
            (int) $before['option_customer_activate'],
            (int) $after['option_customer_activate'],
        );
    }

    public function testUpdateRoundTripsNullableFieldsAsNull(): void
    {
        $storage = $this->sql(BaseInfoStorageInterface::class);

        // All-null except shopName (the only non-null field on the
        // Entity). Round-trip must preserve each null.
        $storage->update(new BaseInfoEntity(
            shopName: 'Just a name',
            shopKana: null,
            shopNameEng: null,
            companyName: null,
            postalCode: null,
            pref: null,
            addr01: null,
            addr02: null,
            phoneNumber: null,
            businessHour: null,
            shopEmail01: null,
            shopMessage: null,
        ));

        $read = $storage->get();
        $this->assertSame('Just a name', $read->shopName);
        $this->assertNull($read->shopKana);
        $this->assertNull($read->shopNameEng);
        $this->assertNull($read->companyName);
        $this->assertNull($read->postalCode);
        $this->assertNull($read->pref);
        $this->assertNull($read->addr01);
        $this->assertNull($read->addr02);
        $this->assertNull($read->phoneNumber);
        $this->assertNull($read->businessHour);
        $this->assertNull($read->shopEmail01);
        $this->assertNull($read->shopMessage);
    }

    public function testUpdatePersistsPrefIdWhenForeignKeyIsSatisfiable(): void
    {
        // mtb_pref is empty in the structure-only dump; seed pref=13 so
        // the FK constraint dtb_base_info.pref_id → mtb_pref.id can be
        // satisfied. Same convention as AddressBookResourceSqlTest.
        $this->insertPref(13, 'Tokyo');

        $storage = $this->sql(BaseInfoStorageInterface::class);
        $storage->update(new BaseInfoEntity(
            shopName: 'With pref',
            shopKana: null,
            shopNameEng: null,
            companyName: null,
            postalCode: null,
            pref: 13,
            addr01: null,
            addr02: null,
            phoneNumber: null,
            businessHour: null,
            shopEmail01: null,
            shopMessage: null,
        ));

        $read = $storage->get();
        $this->assertSame(13, $read->pref);
    }
}
