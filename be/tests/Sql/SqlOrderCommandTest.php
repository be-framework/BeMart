<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlOrderCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlOrderQuery;

/**
 * Storage-layer coverage for {@see SqlOrderCommand} (Phase 2b).
 *
 * Per G-23 the client-observable contract lives in the Resource-layer
 * siblings ({@see \MyVendor\BeMart\Tests\Resource\Sql\AdminOrderStatusResourceSqlTest}
 * / {@see \MyVendor\BeMart\Tests\Resource\Sql\CheckoutResourceSqlTest});
 * this file pins the per-method SQL paths against the SAME column↔field
 * projection {@see SqlOrderQuery} reads back, so a read-after-write
 * round-trips exactly.
 *
 * Surprises this suite locks in:
 *  - `register` is an UPSERT keyed by pre_order_id — it PROMOTES an
 *    existing pre-order row (PROCESSING→NEW, EC-CUBE's "mutate the same
 *    row" semantics) and only INSERTs when no pre-order exists.
 *  - dtb_order.order_status_id has NO FK constraint, so no master-table
 *    seeding is needed (unlike customer_status / login_history_status).
 *  - dtb_order.name01 / name02 are NOT NULL and unmodelled — a fresh
 *    INSERT (admin data-entry order) supplies a placeholder.
 *  - customer_id is an int FK — a non-numeric BeMart handle writes NULL.
 */
final class SqlOrderCommandTest extends AbstractSqlTestCase
{
    /**
     * Build a FinalizedOrderEntity with sensible defaults so each test
     * only states the fields it cares about.
     *
     * @param array<string, mixed> $overrides
     */
    private function entity(array $overrides = []): FinalizedOrderEntity
    {
        $defaults = [
            'orderNo' => 'ORD-DEFAULT',
            'preOrderId' => 'pre-default',
            // Non-numeric default → writes NULL customer_id, so a test
            // that does not seed a dtb_customer row never trips the FK.
            // Tests that care about the customer link pass a numeric id.
            'customerId' => 'anon',
            'paymentMethodId' => 0,
            'subtotal' => 1000,
            'deliveryFeeTotal' => 500,
            'charge' => 0,
            'discount' => 0,
            'tax' => 100,
            'total' => 1600,
            'paymentTotal' => 1600,
            'addPoint' => 16,
            'usePoint' => 0,
            'orderStatus' => FinalizedOrderEntity::STATUS_NEW,
            'orderDate' => '2026-05-20 10:00:00',
            'paymentDate' => '2026-05-20 10:00:00',
        ];
        $v = [...$defaults, ...$overrides];

        return new FinalizedOrderEntity(
            orderNo: $v['orderNo'],
            preOrderId: $v['preOrderId'],
            customerId: $v['customerId'],
            paymentMethodId: $v['paymentMethodId'],
            subtotal: $v['subtotal'],
            deliveryFeeTotal: $v['deliveryFeeTotal'],
            charge: $v['charge'],
            discount: $v['discount'],
            tax: $v['tax'],
            total: $v['total'],
            paymentTotal: $v['paymentTotal'],
            addPoint: $v['addPoint'],
            usePoint: $v['usePoint'],
            orderStatus: $v['orderStatus'],
            orderDate: $v['orderDate'],
            paymentDate: $v['paymentDate'],
        );
    }

    public function testRegisterPromotesExistingPreOrderRow(): void
    {
        // A pre-order row already exists (PROCESSING) — register must
        // UPDATE it in place, NOT insert a duplicate.
        $customerId = $this->insertCustomer();
        $pre = $this->insertOrder([
            'customer_id' => $customerId,
            'pre_order_id' => 'PRE-PROMOTE-1',
            'order_status_id' => FinalizedOrderEntity::STATUS_PROCESSING,
            'subtotal' => 0,
            'total' => 0,
        ]);

        $command = new SqlOrderCommand($this->pdo);
        $command->register($this->entity([
            'orderNo' => 'PROMOTED-001',
            'preOrderId' => 'PRE-PROMOTE-1',
            'customerId' => (string) $customerId,
            'subtotal' => 8000,
            'total' => 9300,
            'paymentTotal' => 9300,
            'orderStatus' => FinalizedOrderEntity::STATUS_NEW,
        ]));

        // Exactly one row carries this pre_order_id — promotion, not insert.
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_order WHERE pre_order_id = :pre',
        );
        $stmt->execute([':pre' => 'PRE-PROMOTE-1']);
        $this->assertSame(1, (int) $stmt->fetchColumn());

        // And the promoted row is the same physical row (id unchanged).
        $stmt = $this->pdo->prepare('SELECT id FROM dtb_order WHERE order_no = :no');
        $stmt->execute([':no' => 'PROMOTED-001']);
        $this->assertSame($pre['id'], (int) $stmt->fetchColumn());

        $query = new SqlOrderQuery($this->pdo);
        $read = $query->byOrderNo('PROMOTED-001');
        $this->assertInstanceOf(FinalizedOrderEntity::class, $read);
        $this->assertSame('PROMOTED-001', $read->orderNo);
        $this->assertSame(8000, $read->subtotal);
        $this->assertSame(9300, $read->total);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $read->orderStatus);
        $this->assertSame((string) $customerId, $read->customerId);
    }

    public function testRegisterPreservesPreOrderContactColumns(): void
    {
        // dtb_order.name01 / name02 are NOT NULL and unmodelled by the
        // entity. Promoting a pre-order must leave them intact (the
        // confirm step captured them).
        $this->insertOrder([
            'pre_order_id' => 'PRE-CONTACT',
            'name01' => 'Yamada',
            'name02' => 'Hanako',
            'order_status_id' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);

        $command = new SqlOrderCommand($this->pdo);
        $command->register($this->entity([
            'orderNo' => 'CONTACT-001',
            'preOrderId' => 'PRE-CONTACT',
        ]));

        $stmt = $this->pdo->prepare(
            'SELECT name01, name02 FROM dtb_order WHERE order_no = :no',
        );
        $stmt->execute([':no' => 'CONTACT-001']);
        $row = $stmt->fetch();
        $this->assertSame('Yamada', $row['name01']);
        $this->assertSame('Hanako', $row['name02']);
    }

    public function testRegisterInsertsFreshRowWhenNoPreOrderExists(): void
    {
        // AdminOrderCreated fabricates preOrderId === orderNo — there is
        // no prior pre-order row, so register must INSERT.
        $customerId = $this->insertCustomer();

        $command = new SqlOrderCommand($this->pdo);
        $command->register($this->entity([
            'orderNo' => 'ADMIN-NEW-001',
            'preOrderId' => 'ADMIN-NEW-001',
            'customerId' => (string) $customerId,
            'subtotal' => 5000,
            'total' => 5500,
            'paymentTotal' => 5500,
            'paymentDate' => '',
        ]));

        $query = new SqlOrderQuery($this->pdo);
        $read = $query->byOrderNo('ADMIN-NEW-001');
        $this->assertInstanceOf(FinalizedOrderEntity::class, $read);
        $this->assertSame('ADMIN-NEW-001', $read->orderNo);
        $this->assertSame(5000, $read->subtotal);
        $this->assertSame(5500, $read->total);
        $this->assertSame((string) $customerId, $read->customerId);
        // Empty paymentDate stored as NULL → reads back as ''.
        $this->assertSame('', $read->paymentDate);
    }

    public function testRegisterInsertSuppliesNotNullNamePlaceholders(): void
    {
        // The fresh-INSERT path must satisfy the NOT NULL name01 / name02
        // columns the entity does not model.
        $command = new SqlOrderCommand($this->pdo);
        $command->register($this->entity([
            'orderNo' => 'ADMIN-NAMES-001',
            'preOrderId' => 'ADMIN-NAMES-001',
        ]));

        $stmt = $this->pdo->prepare(
            'SELECT name01, name02 FROM dtb_order WHERE order_no = :no',
        );
        $stmt->execute([':no' => 'ADMIN-NAMES-001']);
        $row = $stmt->fetch();
        $this->assertNotNull($row['name01']);
        $this->assertNotNull($row['name02']);
    }

    public function testRegisterWritesNullCustomerIdForNonNumericHandle(): void
    {
        // FakeSession emits handles like `customer-001`; dtb_order.customer_id
        // is an int FK — a non-numeric handle must write NULL rather than
        // tripping the FK.
        $command = new SqlOrderCommand($this->pdo);
        $command->register($this->entity([
            'orderNo' => 'NONNUMERIC-CUST',
            'preOrderId' => 'NONNUMERIC-CUST',
            'customerId' => 'customer-001',
        ]));

        $stmt = $this->pdo->prepare(
            'SELECT customer_id FROM dtb_order WHERE order_no = :no',
        );
        $stmt->execute([':no' => 'NONNUMERIC-CUST']);
        $this->assertNull($stmt->fetchColumn());
    }

    public function testRegisterRoundTripsMoneyAndPointColumns(): void
    {
        // Read-after-write parity across every money / point column the
        // SqlOrderQuery projection models.
        $command = new SqlOrderCommand($this->pdo);
        $command->register($this->entity([
            'orderNo' => 'MONEY-001',
            'preOrderId' => 'MONEY-001',
            'subtotal' => 12345,
            'deliveryFeeTotal' => 600,
            'charge' => 200,
            'discount' => 500,
            'tax' => 1234,
            'total' => 13879,
            'paymentTotal' => 13879,
            'addPoint' => 138,
            'usePoint' => 50,
        ]));

        $read = (new SqlOrderQuery($this->pdo))->byOrderNo('MONEY-001');
        $this->assertInstanceOf(FinalizedOrderEntity::class, $read);
        $this->assertSame(12345, $read->subtotal);
        $this->assertSame(600, $read->deliveryFeeTotal);
        $this->assertSame(200, $read->charge);
        $this->assertSame(500, $read->discount);
        $this->assertSame(1234, $read->tax);
        $this->assertSame(13879, $read->total);
        $this->assertSame(13879, $read->paymentTotal);
        $this->assertSame(138, $read->addPoint);
        $this->assertSame(50, $read->usePoint);
    }

    public function testRegisterNormalizesAtomDatetimeForRoundTrip(): void
    {
        // CheckoutCompleted stamps ATOM dates; SqlOrderQuery reads bare
        // `Y-m-d H:i:s`. The normalizer must coerce so a read-after-write
        // yields the same string the order pipeline uses.
        $command = new SqlOrderCommand($this->pdo);
        $command->register($this->entity([
            'orderNo' => 'ATOM-DATE-001',
            'preOrderId' => 'ATOM-DATE-001',
            'orderDate' => '2026-05-20T14:30:00+09:00',
            'paymentDate' => '2026-05-20T14:30:00+09:00',
        ]));

        $read = (new SqlOrderQuery($this->pdo))->byOrderNo('ATOM-DATE-001');
        $this->assertInstanceOf(FinalizedOrderEntity::class, $read);
        $this->assertSame('2026-05-20 14:30:00', $read->orderDate);
        $this->assertSame('2026-05-20 14:30:00', $read->paymentDate);
    }

    public function testRegisterConvertsAtomDatetimeToAsiaTokyo(): void
    {
        // A UTC-stamped ATOM value lands in the Asia/Tokyo wall clock
        // (+09:00) so the persisted column matches the pipeline's TZ.
        $command = new SqlOrderCommand($this->pdo);
        $command->register($this->entity([
            'orderNo' => 'TZ-DATE-001',
            'preOrderId' => 'TZ-DATE-001',
            'orderDate' => '2026-05-20T00:00:00+00:00',
        ]));

        $read = (new SqlOrderQuery($this->pdo))->byOrderNo('TZ-DATE-001');
        $this->assertInstanceOf(FinalizedOrderEntity::class, $read);
        // 00:00 UTC → 09:00 Asia/Tokyo.
        $this->assertSame('2026-05-20 09:00:00', $read->orderDate);
    }

    public function testUpdateOverwritesEditableColumns(): void
    {
        $customerId = $this->insertCustomer();
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'UPD-001',
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
            'charge' => 0,
            'discount' => 0,
            'use_point' => 0,
        ]);

        $query = new SqlOrderQuery($this->pdo);
        $current = $query->byOrderNo('UPD-001');
        $this->assertInstanceOf(FinalizedOrderEntity::class, $current);

        // Merge the way AdminOrderUpdated does — keep everything verbatim,
        // overwrite the three editable fields.
        $merged = $this->entity([
            'orderNo' => $current->orderNo,
            'preOrderId' => $current->preOrderId,
            'customerId' => $current->customerId,
            'paymentMethodId' => $current->paymentMethodId,
            'subtotal' => $current->subtotal,
            'deliveryFeeTotal' => $current->deliveryFeeTotal,
            'charge' => 999,
            'discount' => 250,
            'tax' => $current->tax,
            'total' => $current->total,
            'paymentTotal' => $current->paymentTotal,
            'addPoint' => $current->addPoint,
            'usePoint' => 30,
            'orderStatus' => $current->orderStatus,
            'orderDate' => $current->orderDate,
            'paymentDate' => $current->paymentDate,
        ]);

        $command = new SqlOrderCommand($this->pdo);
        $command->update($merged);

        $read = $query->byOrderNo('UPD-001');
        $this->assertInstanceOf(FinalizedOrderEntity::class, $read);
        $this->assertSame(999, $read->charge);
        $this->assertSame(250, $read->discount);
        $this->assertSame(30, $read->usePoint);
    }

    public function testUpdateLeavesContactColumnsUntouched(): void
    {
        // update() writes only the modelled columns — name01 / name02 are
        // outside the SET list and survive verbatim.
        $this->insertOrder([
            'order_no' => 'UPD-CONTACT',
            'name01' => 'Tanaka',
            'name02' => 'Ichiro',
        ]);

        $command = new SqlOrderCommand($this->pdo);
        $command->update($this->entity([
            'orderNo' => 'UPD-CONTACT',
            'charge' => 777,
        ]));

        $stmt = $this->pdo->prepare(
            'SELECT name01, name02 FROM dtb_order WHERE order_no = :no',
        );
        $stmt->execute([':no' => 'UPD-CONTACT']);
        $row = $stmt->fetch();
        $this->assertSame('Tanaka', $row['name01']);
        $this->assertSame('Ichiro', $row['name02']);
    }

    public function testUpdateIsNoOpForUnknownOrderNo(): void
    {
        // No row matches — WHERE order_no hits nothing, no row created.
        $command = new SqlOrderCommand($this->pdo);
        $command->update($this->entity(['orderNo' => 'GHOST-ORDER']));

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_order WHERE order_no = :no',
        );
        $stmt->execute([':no' => 'GHOST-ORDER']);
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function testUpdateStatusFlipsTheStatusColumn(): void
    {
        $this->insertOrder([
            'order_no' => 'STATUS-001',
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
        ]);

        $command = new SqlOrderCommand($this->pdo);
        $command->updateStatus('STATUS-001', FinalizedOrderEntity::STATUS_DELIVERED);

        $read = (new SqlOrderQuery($this->pdo))->byOrderNo('STATUS-001');
        $this->assertInstanceOf(FinalizedOrderEntity::class, $read);
        $this->assertSame(FinalizedOrderEntity::STATUS_DELIVERED, $read->orderStatus);
    }

    public function testUpdateStatusLeavesOtherColumnsUntouched(): void
    {
        $this->insertOrder([
            'order_no' => 'STATUS-NARROW',
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
            'total' => 4242,
        ]);

        $command = new SqlOrderCommand($this->pdo);
        $command->updateStatus('STATUS-NARROW', FinalizedOrderEntity::STATUS_CANCEL);

        $read = (new SqlOrderQuery($this->pdo))->byOrderNo('STATUS-NARROW');
        $this->assertInstanceOf(FinalizedOrderEntity::class, $read);
        $this->assertSame(FinalizedOrderEntity::STATUS_CANCEL, $read->orderStatus);
        // The narrow flip never touched the total column.
        $this->assertSame(4242, $read->total);
    }

    public function testUpdateStatusIsNoOpForUnknownOrderNo(): void
    {
        // Concurrent-delete race: a missing row is a silent no-op, no
        // fabricated row, no exception (mirrors FakeOrderCommand).
        $command = new SqlOrderCommand($this->pdo);
        $command->updateStatus('VANISHED-ORDER', FinalizedOrderEntity::STATUS_CANCEL);

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_order WHERE order_no = :no',
        );
        $stmt->execute([':no' => 'VANISHED-ORDER']);
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function testRegisterThenUpdateStatusRoundTripsThroughQuery(): void
    {
        // End-to-end storage round-trip: register a fresh order, flip its
        // status, confirm SqlOrderQuery sees the flipped value — proves
        // the write side and read side agree on the column mapping.
        $command = new SqlOrderCommand($this->pdo);
        $command->register($this->entity([
            'orderNo' => 'ROUNDTRIP-001',
            'preOrderId' => 'ROUNDTRIP-001',
            'orderStatus' => FinalizedOrderEntity::STATUS_NEW,
        ]));
        $command->updateStatus('ROUNDTRIP-001', FinalizedOrderEntity::STATUS_PAID);

        $read = (new SqlOrderQuery($this->pdo))->byOrderNo('ROUNDTRIP-001');
        $this->assertInstanceOf(FinalizedOrderEntity::class, $read);
        $this->assertSame(FinalizedOrderEntity::STATUS_PAID, $read->orderStatus);
    }
}
