<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlPaymentMethodAdminStorage;
use MyVendor\BeMart\Be\Reason\Service\SqlPaymentMethodAdminIdGenerator;

use function date;

/**
 * Storage-layer coverage for {@see SqlPaymentMethodAdminStorage}
 * (Phase 2b).
 *
 * Mirrors the shape of {@see SqlBlockStorageTest}. Per G-23 the
 * client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminPaymentResourceSqlTest};
 * the cases below verify the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / boundary / round-trip /
 * nullable-column coercion / dtb_payment_option cascade on remove.
 */
final class SqlPaymentMethodAdminStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $firstId = $this->insertPayment(['payment_method' => '代金引換']);
        $secondId = $this->insertPayment(['payment_method' => 'クレジットカード']);
        $thirdId = $this->insertPayment(['payment_method' => '銀行振込']);

        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $rows = $storage->list();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(PaymentMethodAdminEntity::class, $rows);
        // ORDER BY id ASC.
        $this->assertSame((string) $firstId, $rows[0]->paymentId);
        $this->assertSame((string) $secondId, $rows[1]->paymentId);
        $this->assertSame((string) $thirdId, $rows[2]->paymentId);
        $this->assertSame('代金引換', $rows[0]->paymentMethodName);
        $this->assertSame('クレジットカード', $rows[1]->paymentMethodName);
        $this->assertSame('銀行振込', $rows[2]->paymentMethodName);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $this->assertSame([], $storage->list());
    }

    public function testGetByIdReturnsHydratedEntity(): void
    {
        $id = $this->insertPayment([
            'payment_method' => 'クレジットカード',
            'charge' => 300,
            'rule_min' => 1000,
            'rule_max' => 50000,
            'visible' => 0,
        ]);

        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(PaymentMethodAdminEntity::class, $entity);
        $this->assertSame((string) $id, $entity->paymentId);
        $this->assertSame('クレジットカード', $entity->paymentMethodName);
        $this->assertSame(300, $entity->charge);
        $this->assertSame(1000, $entity->ruleMin);
        $this->assertSame(50000, $entity->ruleMax);
        $this->assertFalse($entity->visible);
    }

    public function testGetByIdCoercesNullableColumns(): void
    {
        // payment_method is nullable in EC-CUBE but the Entity declares
        // it non-null → coalesce NULL → ''. charge is nullable decimal
        // → coalesce NULL → 0. rule_min / rule_max NULL stay NULL.
        $id = $this->insertPayment([
            'payment_method' => null,
            'charge' => null,
            'rule_min' => null,
            'rule_max' => null,
        ]);

        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(PaymentMethodAdminEntity::class, $entity);
        $this->assertSame('', $entity->paymentMethodName);
        $this->assertSame(0, $entity->charge);
        $this->assertNull($entity->ruleMin);
        $this->assertNull($entity->ruleMax);
    }

    public function testGetByIdTruncatesDecimalChargeToInt(): void
    {
        // charge is decimal(12,2); JPY money has no fractional part but
        // an externally-inserted row could carry one. The hydrator
        // truncates to int.
        $id = $this->insertPayment(['charge' => '250.00']);

        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $entity = $storage->getById((string) $id);

        $this->assertInstanceOf(PaymentMethodAdminEntity::class, $entity);
        $this->assertSame(250, $entity->charge);
    }

    public function testGetByIdReturnsNullForMissingRow(): void
    {
        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $this->assertNull($storage->getById('99999999'));
    }

    public function testGetByIdReturnsNullForNonNumericId(): void
    {
        // 32-char hex from FakePaymentMethodAdminIdGenerator can never
        // match an int PK; surface as miss so PaymentMethodAdminUpdated
        // / PaymentMethodAdminDeleted fire their 404 paths instead of a
        // PDO error.
        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $this->assertNull($storage->getById('deadbeefdeadbeefdeadbeefdeadbeef'));
        $this->assertNull($storage->getById('nonexistent-zzz'));
    }

    public function testPutInsertsNewRowWithProvidedId(): void
    {
        $generator = new SqlPaymentMethodAdminIdGenerator($this->pdo);
        $newId = $generator->generate(); // numeric string

        $entity = new PaymentMethodAdminEntity(
            paymentId: $newId,
            paymentMethodName: 'コンビニ決済',
            charge: 200,
            ruleMin: 500,
            ruleMax: 30000,
            visible: true,
        );

        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $storage->put($entity);

        $read = $storage->getById($newId);
        $this->assertInstanceOf(PaymentMethodAdminEntity::class, $read);
        $this->assertSame($newId, $read->paymentId);
        $this->assertSame('コンビニ決済', $read->paymentMethodName);
        $this->assertSame(200, $read->charge);
        $this->assertSame(500, $read->ruleMin);
        $this->assertSame(30000, $read->ruleMax);
        $this->assertTrue($read->visible);

        // list() also sees it.
        $all = $storage->list();
        $this->assertCount(1, $all);
        $this->assertSame($newId, $all[0]->paymentId);
    }

    public function testPutPersistsNullRuleBoundsAndDefaults(): void
    {
        $generator = new SqlPaymentMethodAdminIdGenerator($this->pdo);
        $newId = $generator->generate();
        $storage = new SqlPaymentMethodAdminStorage($this->pdo);

        $storage->put(new PaymentMethodAdminEntity(
            paymentId: $newId,
            paymentMethodName: '代金引換',
            charge: 0,
            ruleMin: null,
            ruleMax: null,
            visible: true,
        ));

        $read = $storage->getById($newId);
        $this->assertInstanceOf(PaymentMethodAdminEntity::class, $read);
        $this->assertNull($read->ruleMin);
        $this->assertNull($read->ruleMax);
        $this->assertSame(0, $read->charge);

        // Raw column probe — fixed defaults to 1, discriminator is
        // 'payment', creator_id is NULL.
        $stmt = $this->pdo->prepare(
            'SELECT fixed, discriminator_type, creator_id '
            . 'FROM dtb_payment WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(1, (int) $row['fixed']);
        $this->assertSame('payment', $row['discriminator_type']);
        $this->assertNull($row['creator_id']);
    }

    public function testPutPersistsVisibleAsTinyint(): void
    {
        // A soft-hidden payment (visible=false) round-trips the same as
        // a visible one — only the Final layer interprets the flag.
        $generator = new SqlPaymentMethodAdminIdGenerator($this->pdo);
        $newId = $generator->generate();
        $storage = new SqlPaymentMethodAdminStorage($this->pdo);

        $storage->put(new PaymentMethodAdminEntity(
            paymentId: $newId,
            paymentMethodName: '廃止予定',
            charge: 0,
            ruleMin: null,
            ruleMax: null,
            visible: false,
        ));

        $read = $storage->getById($newId);
        $this->assertInstanceOf(PaymentMethodAdminEntity::class, $read);
        $this->assertFalse($read->visible);

        $stmt = $this->pdo->prepare(
            'SELECT visible FROM dtb_payment WHERE id = :id',
        );
        $stmt->execute([':id' => (int) $newId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertSame(0, (int) $row['visible']);
    }

    public function testPutIsNoOpForNonNumericIds(): void
    {
        $storage = new SqlPaymentMethodAdminStorage($this->pdo);

        $storage->put(new PaymentMethodAdminEntity(
            paymentId: 'deadbeefdeadbeefdeadbeefdeadbeef',
            paymentMethodName: 'hex id',
            charge: 0,
            ruleMin: null,
            ruleMax: null,
            visible: true,
        ));

        $this->assertSame([], $storage->list());
    }

    public function testPutUpdatesExistingRowInPlace(): void
    {
        // Insert via fixture (so id is known) then re-put via storage
        // with the same id — exercises the UPDATE branch. ALPS defines
        // doUpdatePayment so the UPDATE path is driven by normal admin
        // flows (UpdatePaymentMethodAdminInput / PaymentMethodAdminUpdated).
        $id = $this->insertPayment([
            'payment_method' => 'Old',
            'charge' => 0,
            'rule_min' => null,
            'rule_max' => null,
            'visible' => 1,
        ]);

        $merged = new PaymentMethodAdminEntity(
            paymentId: (string) $id,
            paymentMethodName: 'New',
            charge: 450,
            ruleMin: 2000,
            ruleMax: 80000,
            visible: false,
        );

        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $storage->put($merged);

        $read = $storage->getById((string) $id);
        $this->assertInstanceOf(PaymentMethodAdminEntity::class, $read);
        $this->assertSame('New', $read->paymentMethodName);
        $this->assertSame(450, $read->charge);
        $this->assertSame(2000, $read->ruleMin);
        $this->assertSame(80000, $read->ruleMax);
        $this->assertFalse($read->visible);

        // Row count unchanged (no duplicate INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testRemoveDeletesExistingRow(): void
    {
        $id = $this->insertPayment(['payment_method' => 'doomed']);
        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $this->assertNotNull($storage->getById((string) $id));

        $storage->remove((string) $id);

        $this->assertNull($storage->getById((string) $id));
        $this->assertSame([], $storage->list());
    }

    public function testRemoveCascadesDtbPaymentOptionLinks(): void
    {
        // dtb_payment_option's FK (payment_id → dtb_payment.id) would
        // otherwise raise FK 1451 on the payment DELETE.
        // SqlPaymentMethodAdminStorage::remove pre-DELETEs the link rows
        // so the payment-level delete succeeds regardless of link state.
        $id = $this->insertPayment(['payment_method' => 'linked']);

        // Seed a link row directly. dtb_payment_option FKs both
        // payment_id (→ dtb_payment.id) and delivery_id
        // (→ dtb_delivery.id); the structure-only dump leaves
        // dtb_delivery empty, so seed a parent delivery too.
        // dtb_delivery NOT NULL: visible (DEFAULT 1), create_date,
        // update_date, discriminator_type (id auto / name nullable).
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare(
            'INSERT INTO dtb_delivery '
            . '(id, name, visible, sort_no, create_date, update_date, '
            . 'discriminator_type) '
            . 'VALUES (:id, :name, 1, :sort_no, :created, :updated, '
            . ':discriminator)',
        )->execute([
            ':id' => 1,
            ':name' => 'ヤマト運輸',
            ':sort_no' => 1,
            ':created' => $now,
            ':updated' => $now,
            ':discriminator' => 'delivery',
        ]);
        $this->pdo->prepare(
            'INSERT INTO dtb_payment_option '
            . '(delivery_id, payment_id, discriminator_type) '
            . 'VALUES (:delivery_id, :payment_id, :discriminator)',
        )->execute([
            ':delivery_id' => 1,
            ':payment_id' => $id,
            ':discriminator' => 'paymentoption',
        ]);

        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $storage->remove((string) $id);

        // Payment is gone.
        $this->assertNull($storage->getById((string) $id));

        // Link row is also gone (cleanup, not just FK satisfaction).
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_payment_option WHERE payment_id = :id',
        );
        $stmt->execute([':id' => $id]);
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function testRemoveIsSilentNoOpForMissingId(): void
    {
        $storage = new SqlPaymentMethodAdminStorage($this->pdo);
        $storage->remove('99999999'); // no row, no exception
        $storage->remove('deadbeefdeadbeefdeadbeefdeadbeef'); // hex, no exception
        $storage->remove('nonexistent-zzz'); // non-numeric, no exception
        $this->assertTrue(true);
    }

    public function testSqlPaymentMethodAdminIdGeneratorAllocatesIncrementingIds(): void
    {
        $generator = new SqlPaymentMethodAdminIdGenerator($this->pdo);

        // Empty table → starts at 1.
        $this->assertSame('1', $generator->generate());

        $firstId = $this->insertPayment();
        $secondId = $this->insertPayment();
        $this->assertSame((string) ($secondId + 1), $generator->generate());
        $this->assertGreaterThan($firstId, $secondId);
    }
}
