<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\TaxRuleEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlTaxRuleStorage;
use MyVendor\BeMart\Be\Reason\Service\TaxRuleIdGeneratorInterface;

use function str_contains;

/**
 * Storage-layer coverage for {@see SqlTaxRuleStorage} (Phase 2b).
 *
 * Mirrors the shape of {@see SqlTagStorageTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminTaxRuleResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip.
 */
final class SqlTaxRuleStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $firstId = $this->insertTaxRule(['tax_rate' => 10]);
        $secondId = $this->insertTaxRule(['tax_rate' => 8]);
        $thirdId = $this->insertTaxRule(['tax_rate' => 5]);

        $storage = $this->sql(SqlTaxRuleStorage::class);
        $rules = $storage->list();

        $this->assertCount(3, $rules);
        $this->assertContainsOnlyInstancesOf(TaxRuleEntity::class, $rules);
        // ORDER BY id ASC.
        $this->assertSame((string) $firstId, $rules[0]->taxRuleId);
        $this->assertSame((string) $secondId, $rules[1]->taxRuleId);
        $this->assertSame((string) $thirdId, $rules[2]->taxRuleId);
        $this->assertSame(10.0, $rules[0]->taxRate);
        $this->assertSame(8.0, $rules[1]->taxRate);
        $this->assertSame(5.0, $rules[2]->taxRate);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = $this->sql(SqlTaxRuleStorage::class);
        $this->assertSame([], $storage->list());
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $id = $this->insertTaxRule([
            'tax_rate' => 10,
            'apply_date' => '2024-04-01 00:00:00',
        ]);

        $storage = $this->sql(SqlTaxRuleStorage::class);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(TaxRuleEntity::class, $entity);
        $this->assertSame((string) $id, $entity->taxRuleId);
        $this->assertSame(10.0, $entity->taxRate);
        // rounding_type_id was NULL in the seed (the structure-only
        // dump leaves mtb_rounding_type empty), so hydrate falls back
        // to STD_ROUND = 1.
        $this->assertSame(1, $entity->roundingType);
        // ISO-8601 with the JST offset baked in (matches the Fake
        // projection's shape).
        $this->assertSame('2024-04-01T00:00:00+09:00', $entity->applyDate);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = $this->sql(SqlTaxRuleStorage::class);
        $this->assertNull($storage->getById('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // Hex ids from FakeTaxRuleIdGenerator can never match an int
        // PK; surface as miss so TaxRuleDeleted's 404 path fires
        // instead of a PDO error.
        $storage = $this->sql(SqlTaxRuleStorage::class);
        $this->assertNull($storage->getById('deadbeefdeadbeefdeadbeefdeadbeef'));
        $this->assertNull($storage->getById('nonexistent-zzz'));
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        $generator = $this->sql(TaxRuleIdGeneratorInterface::class);
        $newId = $generator->generate()->value(); // numeric string

        $entity = new TaxRuleEntity(
            taxRuleId: $newId,
            taxRate: 10.0,
            roundingType: 1,
            applyDate: '2024-04-01T00:00:00+09:00',
        );

        $storage = $this->sql(SqlTaxRuleStorage::class);
        $storage->put($entity);

        $read = $storage->getById($newId);
        $this->assertInstanceOf(TaxRuleEntity::class, $read);
        $this->assertSame($newId, $read->taxRuleId);
        $this->assertSame(10.0, $read->taxRate);
        $this->assertSame(1, $read->roundingType);
        $this->assertSame('2024-04-01T00:00:00+09:00', $read->applyDate);

        // list() also sees it.
        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame($newId, $all[0]->taxRuleId);
    }

    public function testPutSerialisesIsoDateToMysqlDatetime(): void
    {
        $generator = $this->sql(TaxRuleIdGeneratorInterface::class);
        $newId = $generator->generate()->value();
        $storage = $this->sql(SqlTaxRuleStorage::class);

        $storage->put(new TaxRuleEntity(
            taxRuleId: $newId,
            taxRate: 8.0,
            roundingType: 1,
            applyDate: '2024-04-01T00:00:00+09:00',
        ));

        // Probe the raw column — the storage strips the offset to
        // MySQL `Y-m-d H:i:s` (server-local interpretation per
        // sql/diff/entity-vs-eccube.md "Datetime columns").
        $stmt = $this->pdo->prepare(
            'SELECT apply_date FROM dtb_tax_rule WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        // Plain MySQL datetime string, no timezone designator.
        $this->assertIsString($row['apply_date']);
        $this->assertSame(19, strlen($row['apply_date']));
        $this->assertFalse(str_contains($row['apply_date'], 'T'));
    }

    public function testPutTruncatesFractionalTaxRate(): void
    {
        // dtb_tax_rule.tax_rate is `decimal(10,0) unsigned` — fractional
        // input is silently truncated by MariaDB at the column
        // boundary. Documented limitation of EC-CUBE 4.3's schema.
        $generator = $this->sql(TaxRuleIdGeneratorInterface::class);
        $newId = $generator->generate()->value();
        $storage = $this->sql(SqlTaxRuleStorage::class);

        $storage->put(new TaxRuleEntity(
            taxRuleId: $newId,
            taxRate: 8.5,
            roundingType: 1,
            applyDate: '2024-04-01T00:00:00+09:00',
        ));

        $read = $storage->getById($newId);
        $this->assertInstanceOf(TaxRuleEntity::class, $read);
        // 8.5 → 8 (or 9, depending on MariaDB rounding mode — both are
        // schema-valid). The assertion captures "no fractional bit
        // survives the round-trip".
        $this->assertSame(0.0, $read->taxRate - (int) $read->taxRate);
    }

    public function testPutIsNoOpForNonNumericIds(): void
    {
        $storage = $this->sql(SqlTaxRuleStorage::class);

        $storage->put(new TaxRuleEntity(
            taxRuleId: 'deadbeefdeadbeefdeadbeefdeadbeef',
            taxRate: 10.0,
            roundingType: 1,
            applyDate: '2024-04-01T00:00:00+09:00',
        ));

        $this->assertSame([], $storage->list());
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        // Insert via fixture (so id is known) then re-put via storage
        // with the same id — exercises the UPDATE branch (defensive
        // path for idempotent replays; the production Final does not
        // re-emit existing ids per the ALPS "no doUpdateTaxRule" rule).
        $id = $this->insertTaxRule([
            'tax_rate' => 10,
            'apply_date' => '2024-04-01 00:00:00',
        ]);

        $merged = new TaxRuleEntity(
            taxRuleId: (string) $id,
            taxRate: 8.0,
            roundingType: 1,
            applyDate: '2025-04-01T00:00:00+09:00',
        );

        $storage = $this->sql(SqlTaxRuleStorage::class);
        $storage->put($merged);

        $read = $storage->getById((string) $id);
        $this->assertInstanceOf(TaxRuleEntity::class, $read);
        $this->assertSame(8.0, $read->taxRate);
        $this->assertSame('2025-04-01T00:00:00+09:00', $read->applyDate);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $id = $this->insertTaxRule(['tax_rate' => 10]);
        $storage = $this->sql(SqlTaxRuleStorage::class);
        $this->assertNotNull($storage->getById((string) $id));

        $storage->remove((string) $id);

        $this->assertNull($storage->getById((string) $id));
        $this->assertSame([], $storage->list());
    }

    public function testRemoveIsSilentNoOpForMissingId(): void
    {
        $storage = $this->sql(SqlTaxRuleStorage::class);
        $storage->remove('99999999'); // no row, no exception
        $storage->remove('deadbeefdeadbeefdeadbeefdeadbeef'); // hex, no exception
        $storage->remove('nonexistent-zzz'); // non-numeric, no exception
        $this->assertTrue(true);
    }

    public function testTaxRuleIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = $this->sql(TaxRuleIdGeneratorInterface::class);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->generate()->value());

        $firstId = $this->insertTaxRule();
        $secondId = $this->insertTaxRule();
        $this->assertSame((string) ($secondId + 1), $generator->generate()->value());
        $this->assertGreaterThan($firstId, $secondId);
    }
}
