<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlOrderQuery;

final class SqlOrderQueryTest extends AbstractSqlTestCase
{
    public function testListByCustomerReturnsRowsWhenCustomerHasOrders(): void
    {
        $customerId = $this->insertCustomer();
        $this->insertOrder([
            'customer_id' => $customerId,
            'total' => 1500,
            'order_date' => '2026-05-10 10:00:00',
        ]);
        $this->insertOrder([
            'customer_id' => $customerId,
            'total' => 2500,
            'order_date' => '2026-05-11 10:00:00',
        ]);

        $query = new SqlOrderQuery($this->pdo);
        $orders = $query->listByCustomer((string) $customerId);

        $this->assertCount(2, $orders);
        $this->assertContainsOnlyInstancesOf(FinalizedOrderEntity::class, $orders);
        $totals = array_map(static fn (FinalizedOrderEntity $o) => $o->total, $orders);
        sort($totals);
        $this->assertSame([1500, 2500], $totals);
    }

    public function testListByCustomerReturnsEmptyWhenCustomerHasNoOrders(): void
    {
        $customerId = $this->insertCustomer();
        // Insert an order for an unrelated customer to make sure the
        // filter is doing real work.
        $otherCustomerId = $this->insertCustomer();
        $this->insertOrder(['customer_id' => $otherCustomerId]);

        $query = new SqlOrderQuery($this->pdo);
        $this->assertSame([], $query->listByCustomer((string) $customerId));
    }

    public function testListByCustomerOrdersByOrderDateDescending(): void
    {
        $customerId = $this->insertCustomer();
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'ORD-MIDDLE',
            'order_date' => '2026-05-10 10:00:00',
        ]);
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'ORD-LATEST',
            'order_date' => '2026-05-15 10:00:00',
        ]);
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'ORD-OLDEST',
            'order_date' => '2026-05-01 10:00:00',
        ]);

        $query = new SqlOrderQuery($this->pdo);
        $orders = $query->listByCustomer((string) $customerId);

        $orderNos = array_map(static fn (FinalizedOrderEntity $o) => $o->orderNo, $orders);
        $this->assertSame(['ORD-LATEST', 'ORD-MIDDLE', 'ORD-OLDEST'], $orderNos);
    }

    public function testListByCustomerHonoursLimitAndOffset(): void
    {
        $customerId = $this->insertCustomer();
        for ($i = 1; $i <= 5; $i++) {
            $this->insertOrder([
                'customer_id' => $customerId,
                'order_no' => sprintf('ORD-%02d', $i),
                'order_date' => sprintf('2026-05-%02d 10:00:00', $i),
            ]);
        }

        $query = new SqlOrderQuery($this->pdo);
        // Newest first → ORD-05, ORD-04, ORD-03, ORD-02, ORD-01.
        // Skip 2, take 2 → ORD-03, ORD-02.
        $page = $query->listByCustomer((string) $customerId, limit: 2, offset: 2);

        $orderNos = array_map(static fn (FinalizedOrderEntity $o) => $o->orderNo, $page);
        $this->assertSame(['ORD-03', 'ORD-02'], $orderNos);
    }

    public function testListByCustomerExcludesPreOrders(): void
    {
        $customerId = $this->insertCustomer();
        // One legitimately finalized order, one pre-order
        // (order_status_id = 8 = PROCESSING) — only the finalized one
        // should come back.
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'FINAL-1',
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
        ]);
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'PROCESSING-1',
            'order_status_id' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);

        $query = new SqlOrderQuery($this->pdo);
        $orders = $query->listByCustomer((string) $customerId);
        $this->assertCount(1, $orders);
        $this->assertSame('FINAL-1', $orders[0]->orderNo);
    }

    public function testListAllReturnsEveryFinalizedOrder(): void
    {
        $a = $this->insertCustomer();
        $b = $this->insertCustomer();
        $this->insertOrder(['customer_id' => $a, 'order_date' => '2026-05-10 10:00:00']);
        $this->insertOrder(['customer_id' => $b, 'order_date' => '2026-05-11 10:00:00']);
        $this->insertOrder([
            'customer_id' => $b,
            'order_date' => '2026-05-12 10:00:00',
            'order_status_id' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);

        $query = new SqlOrderQuery($this->pdo);
        $orders = $query->listAll();
        $this->assertCount(2, $orders, 'pre-orders are excluded from listAll too');
    }

    public function testByOrderNoReturnsRowWhenFound(): void
    {
        $customerId = $this->insertCustomer();
        $this->insertOrder([
            'customer_id' => $customerId,
            'order_no' => 'LOOKUP-001',
            'total' => 4242,
        ]);

        $query = new SqlOrderQuery($this->pdo);
        $order = $query->byOrderNo('LOOKUP-001');
        $this->assertNotNull($order);
        $this->assertSame('LOOKUP-001', $order->orderNo);
        $this->assertSame(4242, $order->total);
        $this->assertSame((string) $customerId, $order->customerId);
    }

    public function testByOrderNoReturnsNullWhenMissing(): void
    {
        $this->insertOrder();
        $query = new SqlOrderQuery($this->pdo);
        $this->assertNull($query->byOrderNo('NOPE'));
    }

    public function testByOrderNoReturnsNullForPreOrders(): void
    {
        // A row with the matching order_no but PROCESSING status — not
        // a finalized order, so byOrderNo must NOT surface it.
        $this->insertOrder([
            'order_no' => 'PROC-001',
            'order_status_id' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);
        $query = new SqlOrderQuery($this->pdo);
        $this->assertNull($query->byOrderNo('PROC-001'));
    }

    public function testByPreOrderIdReturnsOrderEntityForPreOrder(): void
    {
        // payment_id intentionally left null — dtb_payment is empty in
        // the structure-only dump, so any non-null value would trip the
        // FK constraint. The hydration test below covers the int cast
        // (null payment_id reads back as 0 — acceptable for a smoke).
        $customerId = $this->insertCustomer();
        $this->insertOrder([
            'customer_id' => $customerId,
            'pre_order_id' => 'PRE-001',
            'payment_id' => null,
            'delivery_fee_total' => 600,
            'order_status_id' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);

        $query = new SqlOrderQuery($this->pdo);
        $order = $query->byPreOrderId('PRE-001');
        $this->assertInstanceOf(OrderEntity::class, $order);
        $this->assertSame('PRE-001', $order->preOrderId);
        $this->assertSame((string) $customerId, $order->customerId);
        $this->assertSame(0, $order->paymentMethodId);
        $this->assertSame(600, $order->deliveryFeeTotal);
        $this->assertSame([], $order->items, 'items deferred to Phase 2b cart join');
    }

    public function testByPreOrderIdReturnsNullForFinalizedRows(): void
    {
        // Same pre_order_id, but a NEW (finalized) row — must NOT
        // come back through byPreOrderId.
        $this->insertOrder([
            'pre_order_id' => 'PRE-NEW',
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
        ]);

        $query = new SqlOrderQuery($this->pdo);
        $this->assertNull($query->byPreOrderId('PRE-NEW'));
    }

    public function testItemsByOrderNoJoinsThroughOrderId(): void
    {
        $order = $this->insertOrder(['order_no' => 'ITEM-ORD-001']);
        $this->insertOrderItem($order['id'], [
            'product_name' => 'Widget',
            'product_code' => 'WID-1',
            'price' => 800,
            'quantity' => 2,
        ]);
        $this->insertOrderItem($order['id'], [
            'product_name' => 'Gizmo',
            'product_code' => 'GIZ-2',
            'price' => 1500,
            'quantity' => 1,
        ]);

        $query = new SqlOrderQuery($this->pdo);
        $items = $query->itemsByOrderNo('ITEM-ORD-001');

        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(OrderItemEntity::class, $items);
        $this->assertSame('ITEM-ORD-001', $items[0]->orderNo);
        $this->assertSame('Widget', $items[0]->productName);
        $this->assertSame('WID-1', $items[0]->productCode);
        $this->assertSame(800, $items[0]->unitPrice);
        $this->assertSame(2, $items[0]->quantity);
        $this->assertSame('Gizmo', $items[1]->productName);
    }

    public function testItemsByOrderNoReturnsEmptyWhenNoItems(): void
    {
        $this->insertOrder(['order_no' => 'EMPTY-ORD']);
        $query = new SqlOrderQuery($this->pdo);
        $this->assertSame([], $query->itemsByOrderNo('EMPTY-ORD'));
    }
}
