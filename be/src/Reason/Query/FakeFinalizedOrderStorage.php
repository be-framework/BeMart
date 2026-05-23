<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryMailEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryShippingEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;

use function array_map;
use function array_slice;
use function strcmp;
use function usort;

/**
 * In-memory store for finalized Orders (orderStatus=NEW).
 *
 * Singleton-bound so OrderCommand writes and CheckoutCompletedTest reads
 * the same map. Phase 2 swaps for a Ray.MediaQuery command against
 * dtb_order that flips the row from PROCESSING(8) to NEW(1).
 *
 * Order-item rows (dtb_order_item in EC-CUBE) live in a parallel map keyed
 * by orderNo. Items are tracked separately from the order header because
 * Pilot 5 deferred them — Phase 2 will materialise them at checkout time
 * out of the cart/pre-order, but for Pilot 12 (doReorder) we need a
 * `itemsByOrderNo` read path now. Seeding (see constructor) installs a
 * past order for customer-001 so reorder-style flows have something to
 * read without first running checkout.
 *
 * Phase 3 enrichment adds a third parallel map: the order-history detail
 * (message / paymentMethod / per-shipping address blocks / mail-delivery
 * log) keyed by orderNo, surfaced via `historyByOrderNo` as the enriched
 * {@see OrderHistoryEntity}. When an order has no history detail recorded
 * (e.g. a checkout-created order without shipping rows) the projection
 * degrades gracefully — empty message / payment, a single address-less
 * shipping block carrying the flat item list, no mail history — keeping
 * the Fake parallel to {@see SqlOrderQuery::historyByOrderNo}.
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

    /**
     * Order-history detail keyed by orderNo. Each value carries the
     * message, payment-method name, per-shipping address blocks and the
     * mail-delivery log — the data EC-CUBE's history.twig renders beyond
     * the order header.
     *
     * @var array<string, array{
     *   message: string,
     *   paymentMethod: string,
     *   shippings: list<OrderHistoryShippingEntity>,
     *   mailHistories: list<OrderHistoryMailEntity>
     * }>
     */
    private array $historyDetails = [];

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
     * Record the order-history detail for an order — message, payment
     * method, per-shipping address blocks and the mail-delivery log.
     * Phase 3 enrichment; consumed by `historyByOrderNo`.
     *
     * @param list<OrderHistoryShippingEntity> $shippings
     * @param list<OrderHistoryMailEntity>     $mailHistories
     */
    public function putHistoryDetail(
        string $orderNo,
        string $message,
        string $paymentMethod,
        array $shippings,
        array $mailHistories,
    ): void {
        $this->historyDetails[$orderNo] = [
            'message' => $message,
            'paymentMethod' => $paymentMethod,
            'shippings' => $shippings,
            'mailHistories' => $mailHistories,
        ];
    }

    /**
     * Build the enriched order-history projection for one finalized order
     * (Phase 3 enrichment — the screen aggregate behind goMypageHistory).
     *
     * Returns null when the orderNo is unknown — same miss semantics as
     * `getByOrderNo`. When the order exists but has no history detail
     * recorded, the projection degrades gracefully: empty message /
     * payment method, no mail history, and a single address-less
     * shipping block carrying the flat `itemsByOrderNo` list — the same
     * shape `SqlOrderQuery::historyByOrderNo` returns for an order that
     * has one dtb_shipping row and no dtb_payment / dtb_mail_history
     * matches.
     */
    public function historyByOrderNo(string $orderNo): OrderHistoryEntity|null
    {
        $order = $this->orders[$orderNo] ?? null;
        if ($order === null) {
            return null;
        }

        $detail = $this->historyDetails[$orderNo] ?? null;
        if ($detail === null) {
            $detail = [
                'message' => '',
                'paymentMethod' => '',
                'shippings' => [
                    new OrderHistoryShippingEntity(
                        name01: '',
                        name02: '',
                        kana01: '',
                        kana02: '',
                        postalCode: '',
                        prefName: '',
                        addr01: '',
                        addr02: '',
                        phoneNumber: '',
                        deliveryName: '',
                        deliveryDate: '',
                        deliveryTime: '',
                        items: array_map(
                            static fn (OrderItemEntity $i): OrderHistoryItemEntity
                                => new OrderHistoryItemEntity(
                                    productCode: $i->productCode,
                                    productName: $i->productName,
                                    quantity: $i->quantity,
                                    unitPrice: $i->unitPrice,
                                ),
                            $this->items[$orderNo] ?? [],
                        ),
                    ),
                ],
                'mailHistories' => [],
            ];
        }

        return new OrderHistoryEntity(
            orderNo: $order->orderNo,
            customerId: $order->customerId,
            message: $detail['message'],
            paymentMethod: $detail['paymentMethod'],
            subtotal: $order->subtotal,
            deliveryFeeTotal: $order->deliveryFeeTotal,
            charge: $order->charge,
            discount: $order->discount,
            tax: $order->tax,
            total: $order->total,
            paymentTotal: $order->paymentTotal,
            addPoint: $order->addPoint,
            usePoint: $order->usePoint,
            orderStatus: $order->orderStatus,
            orderDate: $order->orderDate,
            paymentDate: $order->paymentDate,
            shippings: $detail['shippings'],
            mailHistories: $detail['mailHistories'],
        );
    }

    /**
     * Return every finalized order sorted newest first by `orderDate`,
     * advanced by `$offset` and capped at `$limit`. Wave 7 (goOrderList)
     * uses this for the admin grid.
     *
     * @return list<FinalizedOrderEntity>
     */
    public function getAll(int $limit, int $offset = 0): array
    {
        $all = [];
        foreach ($this->orders as $order) {
            $all[] = $order;
        }

        usort(
            $all,
            static fn (FinalizedOrderEntity $a, FinalizedOrderEntity $b): int
                => strcmp($b->orderDate, $a->orderDate),
        );

        return array_slice($all, $offset, $limit);
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

        // Phase 3 enrichment — the order-history detail the
        // goMypageHistory screen renders beyond the order header: the
        // customer's order message, the payment method, one shipping
        // address block carrying both line items, and the order's
        // mail-delivery log. Mirrors a single-shipping past order.
        $this->historyDetails[$orderNo] = [
            'message' => '配送は平日希望です。',
            'paymentMethod' => '銀行振込',
            'shippings' => [
                new OrderHistoryShippingEntity(
                    name01: '山田',
                    name02: '太郎',
                    kana01: 'ヤマダ',
                    kana02: 'タロウ',
                    postalCode: '530-0001',
                    prefName: '大阪府',
                    addr01: '大阪市北区梅田',
                    addr02: '1-2-3',
                    phoneNumber: '0612345678',
                    deliveryName: 'サンプル宅配便',
                    deliveryDate: '2026-04-03',
                    deliveryTime: '午前中',
                    items: [
                        new OrderHistoryItemEntity(
                            productCode: 'sample-001',
                            productName: 'サンプル商品 A',
                            quantity: 1,
                            unitPrice: 1200,
                        ),
                        new OrderHistoryItemEntity(
                            productCode: 'sample-002',
                            productName: 'Sample Product B',
                            quantity: 1,
                            unitPrice: 9800,
                        ),
                    ],
                ),
            ],
            'mailHistories' => [
                new OrderHistoryMailEntity(
                    sendDate: '2026-04-01 10:05:00',
                    mailSubject: 'ご注文ありがとうございます',
                    mailBody: "この度はご注文いただきありがとうございます。\n商品の発送まで今しばらくお待ちください。",
                ),
            ],
        ];
    }
}
