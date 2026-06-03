<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;

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

        $query = $this->sql(OrderQueryInterface::class);
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

        $query = $this->sql(OrderQueryInterface::class);
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

        $query = $this->sql(OrderQueryInterface::class);
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

        $query = $this->sql(OrderQueryInterface::class);
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

        $query = $this->sql(OrderQueryInterface::class);
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

        $query = $this->sql(OrderQueryInterface::class);
        $orders = $query->list();
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

        $query = $this->sql(OrderQueryInterface::class);
        $order = $query->byOrderNo('LOOKUP-001');
        $this->assertNotNull($order);
        $this->assertSame('LOOKUP-001', $order->orderNo);
        $this->assertSame(4242, $order->total);
        $this->assertSame((string) $customerId, $order->customerId);
    }

    public function testByOrderNoReturnsNullWhenMissing(): void
    {
        $this->insertOrder();
        $query = $this->sql(OrderQueryInterface::class);
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
        $query = $this->sql(OrderQueryInterface::class);
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

        $query = $this->sql(OrderQueryInterface::class);
        $order = $query->byPreOrderId('PRE-001');
        $this->assertInstanceOf(OrderEntity::class, $order);
        $this->assertSame('PRE-001', $order->preOrderId);
        $this->assertSame((string) $customerId, $order->customerId);
        $this->assertSame(0, $order->paymentMethodId);
        $this->assertSame(600, $order->deliveryFeeTotal);
        $this->assertSame([], $order->items, 'no cart raised this pre-order → empty item vector');
    }

    public function testByPreOrderIdAggregatesCartLinesWithProductName(): void
    {
        // The pre-order's items are read from the cart it was raised from
        // (linked by pre_order_id), joined through to the product so the
        // display name is available for the checkout snapshot.
        $customerId = $this->insertCustomer();
        $this->insertOrder([
            'customer_id' => $customerId,
            'pre_order_id' => 'PRE-CART-001',
            'payment_id' => null,
            'delivery_fee_total' => 500,
            'order_status_id' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);

        $productA = $this->insertProduct(['name' => 'Cart Line A', 'product_code' => 'CART-A', 'price02' => 1000]);
        $productB = $this->insertProduct(['name' => 'Cart Line B', 'product_code' => 'CART-B', 'price02' => 500]);
        $cart = $this->insertCart([
            'cart_key' => 'pre-cart_10',
            'customer_id' => $customerId,
            'pre_order_id' => 'PRE-CART-001',
        ]);
        $this->insertCartItem($cart['id'], $this->defaultProductClassId($productA), ['price' => 1000, 'quantity' => 2]);
        $this->insertCartItem($cart['id'], $this->defaultProductClassId($productB), ['price' => 500, 'quantity' => 1]);

        $order = $this->sql(OrderQueryInterface::class)->byPreOrderId('PRE-CART-001');
        $this->assertInstanceOf(OrderEntity::class, $order);
        $this->assertCount(2, $order->items);
        $this->assertSame('CART-A', $order->items[0]->productCode);
        $this->assertSame('Cart Line A', $order->items[0]->productName);
        $this->assertSame(2, $order->items[0]->quantity);
        $this->assertSame(1000, $order->items[0]->price);
        $this->assertSame('CART-B', $order->items[1]->productCode);
        $this->assertSame('Cart Line B', $order->items[1]->productName);
    }

    public function testByPreOrderIdReturnsNullForFinalizedRows(): void
    {
        // Same pre_order_id, but a NEW (finalized) row — must NOT
        // come back through byPreOrderId.
        $this->insertOrder([
            'pre_order_id' => 'PRE-NEW',
            'order_status_id' => FinalizedOrderEntity::STATUS_NEW,
        ]);

        $query = $this->sql(OrderQueryInterface::class);
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

        $query = $this->sql(OrderQueryInterface::class);
        $items = $query->listByOrderNo('ITEM-ORD-001');

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
        $query = $this->sql(OrderQueryInterface::class);
        $this->assertSame([], $query->listByOrderNo('EMPTY-ORD'));
    }

    public function testHistoryByOrderNoReturnsNullWhenMissing(): void
    {
        $query = $this->sql(OrderQueryInterface::class);
        $this->assertNull($query->item('NO-SUCH-ORDER'));
    }

    public function testHistoryByOrderNoReturnsNullForPreOrders(): void
    {
        $this->insertOrder([
            'order_no' => 'HIST-PROC',
            'order_status_id' => FinalizedOrderEntity::STATUS_PROCESSING,
        ]);
        $query = $this->sql(OrderQueryInterface::class);
        $this->assertNull($query->item('HIST-PROC'));
    }

    public function testHistoryByOrderNoJoinsHeaderMessageAndPaymentMethod(): void
    {
        $customerId = $this->insertCustomer();
        $paymentId = $this->insertPayment(['payment_method' => '銀行振込']);
        $this->insertOrder([
            'customer_id' => $customerId,
            'payment_id' => $paymentId,
            'order_no' => 'HIST-001',
            'message' => '配送は平日希望です。',
            'total' => 12700,
            'payment_total' => 12700,
            'add_point' => 127,
        ]);

        $query = $this->sql(OrderQueryInterface::class);
        $history = $query->item('HIST-001');

        $this->assertNotNull($history);
        $this->assertSame('HIST-001', $history->orderNo);
        $this->assertSame((string) $customerId, $history->customerId);
        $this->assertSame('配送は平日希望です。', $history->message);
        $this->assertSame('銀行振込', $history->paymentMethod);
        $this->assertSame(12700, $history->total);
        $this->assertSame(127, $history->addPoint);
    }

    public function testHistoryByOrderNoDegradesGracefullyWhenPaymentMissing(): void
    {
        // payment_id NULL — the LEFT JOIN must not drop the order; the
        // payment-method name degrades to the empty string.
        $this->insertOrder([
            'order_no' => 'HIST-NOPAY',
            'payment_id' => null,
            'message' => null,
        ]);

        $query = $this->sql(OrderQueryInterface::class);
        $history = $query->item('HIST-NOPAY');

        $this->assertNotNull($history);
        $this->assertSame('', $history->paymentMethod);
        $this->assertSame('', $history->message);
        $this->assertSame([], $history->shippings);
        $this->assertSame([], $history->mailHistories);
    }

    public function testHistoryByOrderNoCarriesPerShippingBlocksWithGroupedItems(): void
    {
        $order = $this->insertOrder(['order_no' => 'HIST-SHIP']);
        $shippingId = $this->insertShipping([
            'order_id' => $order['id'],
            'name01' => '山田',
            'name02' => '太郎',
            'kana01' => 'ヤマダ',
            'kana02' => 'タロウ',
            'postal_code' => '5300001',
            'addr01' => '大阪市北区梅田',
            'addr02' => '1-2-3',
            'phone_number' => '0612345678',
            'delivery_name' => 'サンプル宅配便',
            'delivery_date' => '2026-04-03 00:00:00',
            'delivery_time' => '午前中',
        ]);
        $this->insertOrderItem($order['id'], [
            'shipping_id' => $shippingId,
            'product_name' => 'Widget',
            'product_code' => 'WID-1',
            'price' => 1200,
            'quantity' => 2,
        ]);
        $this->insertOrderItem($order['id'], [
            'shipping_id' => $shippingId,
            'product_name' => 'Gizmo',
            'product_code' => 'GIZ-2',
            'price' => 9800,
            'quantity' => 1,
        ]);

        $query = $this->sql(OrderQueryInterface::class);
        $history = $query->item('HIST-SHIP');

        $this->assertNotNull($history);
        $this->assertCount(1, $history->shippings);
        $shipping = $history->shippings[0];
        $this->assertSame('山田', $shipping->name01);
        $this->assertSame('太郎', $shipping->name02);
        $this->assertSame('ヤマダ', $shipping->kana01);
        $this->assertSame('5300001', $shipping->postalCode);
        $this->assertSame('大阪市北区梅田', $shipping->addr01);
        $this->assertSame('サンプル宅配便', $shipping->deliveryName);
        $this->assertSame('午前中', $shipping->deliveryTime);
        // mtb_pref is empty in the structure-only dump — prefName
        // degrades to the empty string via the LEFT JOIN.
        $this->assertSame('', $shipping->prefName);

        $this->assertCount(2, $shipping->items);
        $this->assertSame('WID-1', $shipping->items[0]->productCode);
        $this->assertSame('Widget', $shipping->items[0]->productName);
        $this->assertSame(1200, $shipping->items[0]->unitPrice);
        $this->assertSame(2, $shipping->items[0]->quantity);
        $this->assertSame('Gizmo', $shipping->items[1]->productName);
    }

    public function testHistoryByOrderNoCarriesMailHistoriesOldestFirst(): void
    {
        $order = $this->insertOrder(['order_no' => 'HIST-MAIL']);
        $this->insertMailHistory($order['id'], [
            'send_date' => '2026-04-02 09:00:00',
            'mail_subject' => '発送のお知らせ',
            'mail_body' => '商品を発送しました。',
        ]);
        $this->insertMailHistory($order['id'], [
            'send_date' => '2026-04-01 10:05:00',
            'mail_subject' => 'ご注文ありがとうございます',
            'mail_body' => 'ご注文を承りました。',
        ]);

        $query = $this->sql(OrderQueryInterface::class);
        $history = $query->item('HIST-MAIL');

        $this->assertNotNull($history);
        $this->assertCount(2, $history->mailHistories);
        // Oldest send first — matches the order EC-CUBE's history.twig
        // walks the MailHistories collection.
        $this->assertSame('ご注文ありがとうございます', $history->mailHistories[0]->mailSubject);
        $this->assertSame('発送のお知らせ', $history->mailHistories[1]->mailSubject);
    }
}
