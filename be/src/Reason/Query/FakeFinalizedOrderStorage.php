<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;

use function array_slice;
use function strcmp;
use function usort;

/**
 * In-memory store for finalized Orders (orderStatus=NEW).
 *
 * Singleton-bound so OrderCommand writes and CheckoutCompletedTest reads
 * the same map. Phase 2 swaps for a Ray.MediaQuery UPDATE against
 * dtb_order that flips the row from PROCESSING(8) to NEW(1).
 *
 * Order-item rows (dtb_order_item in EC-CUBE) live in a parallel map keyed
 * by orderNo. Items are tracked separately from the order header because
 * Pilot 5 deferred them — Phase 2 will materialise them at checkout time
 * out of the cart/pre-order, but for Pilot 12 (doReorder) we need a
 * `itemsByOrderNo` read path now. Seeding (see constructor) installs a
 * past order for customer-001 so reorder-style flows have something to
 * read without first running checkout.
 */
final class FakeFinalizedOrderStorage
{
    /**
     * Seed order-no for the pre-populated customer-001 past order. Pilot 12
     * (doReorder) reads its items via `itemsByOrderNo`. The string is a
     * 32-char hex that mimics what FakeOrderNumberGenerator produces.
     */
    public const SEED_ORDER_NO = 'past0000000000000000000000000001';

    /** @var array<string, FinalizedOrderEntity> */
    private array $orders = [];

    /** @var array<string, list<OrderItemEntity>> */
    private array $items = [];

    public function __construct()
    {
        $this->seedPastOrder();
    }

    public function put(FinalizedOrderEntity $order): void
    {
        $this->orders[$order->orderNo] = $order;
    }

    /** @param list<OrderItemEntity> $items */
    public function putItems(string $orderNo, array $items): void
    {
        $this->items[$orderNo] = $items;
    }

    public function getByOrderNo(string $orderNo): FinalizedOrderEntity|null
    {
        return $this->orders[$orderNo] ?? null;
    }

    public function getByPreOrderId(string $preOrderId): FinalizedOrderEntity|null
    {
        foreach ($this->orders as $order) {
            if ($order->preOrderId === $preOrderId) {
                return $order;
            }
        }

        return null;
    }

    /**
     * Return the customer's finalized orders sorted newest first (by
     * `orderDate`), advanced by `$offset` rows and capped to the next
     * `$limit` rows. The goMypage dashboard pulls the head of the list
     * (limit=5, offset=0); goOrderHistory pages through the full list
     * (default limit=50, with `$offset` walking subsequent pages).
     *
     * @return list<FinalizedOrderEntity>
     */
    public function getByCustomerId(string $customerId, int $limit, int $offset = 0): array
    {
        $matching = [];
        foreach ($this->orders as $order) {
            if ($order->customerId === $customerId) {
                $matching[] = $order;
            }
        }

        usort(
            $matching,
            static fn (FinalizedOrderEntity $a, FinalizedOrderEntity $b): int
                => strcmp($b->orderDate, $a->orderDate),
        );

        return array_slice($matching, $offset, $limit);
    }

    /** @return list<OrderItemEntity> */
    public function itemsByOrderNo(string $orderNo): array
    {
        return $this->items[$orderNo] ?? [];
    }

    /**
     * Install one past finalized order for customer-001 plus a couple of
     * order-item rows. Pilot 12 (doReorder) needs at least one historical
     * order with items to verify the read path; the values mirror an
     * average shopping cart (two products from the existing product
     * fixture, both with non-null stock).
     */
    private function seedPastOrder(): void
    {
        $orderNo = self::SEED_ORDER_NO;
        $this->orders[$orderNo] = new FinalizedOrderEntity(
            orderNo: $orderNo,
            preOrderId: 'past00000000000000000000000000000000past',
            customerId: 'customer-001',
            paymentMethodId: 2,
            subtotal: 11000,
            deliveryFeeTotal: 600,
            charge: 0,
            discount: 0,
            tax: 1100,
            total: 12700,
            paymentTotal: 12700,
            addPoint: 127,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: '2026-04-01 10:00:00',
            paymentDate: '2026-04-01 10:00:00',
        );
        $this->items[$orderNo] = [
            new OrderItemEntity(
                orderNo: $orderNo,
                productCode: 'sample-001',
                productName: 'サンプル商品 A',
                quantity: 1,
                unitPrice: 1200,
            ),
            new OrderItemEntity(
                orderNo: $orderNo,
                productCode: 'sample-002',
                productName: 'Sample Product B',
                quantity: 1,
                unitPrice: 9800,
            ),
        ];
    }
}
