<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderItemQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function count;

/**
 * Mypage fetched — Final, the logged-in customer's dashboard
 * projection.
 *
 *   GetMypageInput → MypageFetched  (Direct, safe read)
 *
 * Aggregates three independent reads:
 *
 *   - `CustomerQuery::findById`     → basic profile fields
 *   - `OrderQuery::listByCustomer`  → most-recent orders (capped)
 *   - `FavoriteStorage::listByCustomer` → just the count (the full
 *      list belongs to a dedicated favorites resource, not the
 *      dashboard — keep this projection shallow)
 *
 * AUTHN: the customerId comes from SessionInterface. A null session
 * raises UnauthenticatedException — the BEAR layer maps this to 401.
 * If the session points to a non-existent customer (deleted /
 * expired), we likewise raise UnauthenticatedException rather than
 * surfacing a "customer not found" signal across the AAA boundary
 * (Pilot 8 lesson).
 *
 * The `recentOrders` list is a projection — not the FinalizedOrderEntity
 * itself — so the dashboard renders a flat structure and the entity's
 * internal layout (paymentTotal, addPoint, …) does not leak into the
 * HTTP body.
 *
 * Phase 3 enrichment — each `recentOrders` row now carries an `items`
 * sub-array (the order's line-item snapshot). EC-CUBE's `Mypage/index.twig`
 * renders a per-order product list (`Order.MergedProductOrderItems`)
 * under each order's header; the earlier flat projection carried no
 * items, so the `ec-historyRole__detail` block rendered empty. The
 * line items are read via `OrderQuery::itemsByOrderNo` — the same read
 * path Pilot 12 (doReorder) uses — so no widely-shared Entity is
 * mutated and Fake/SQL parity holds structurally (both back ends
 * implement `itemsByOrderNo`). For the dashboard's small cap
 * (`orderLimit` defaults to 5) the per-order item read is bounded.
 */
final readonly class MypageFetched
{
    public string $customerId;
    public string $email;
    public string $name01;
    public string $name02;

    /**
     * @var list<array{
     *   orderNo: string, total: int, orderDate: string, orderStatus: int,
     *   items: list<array{productCode: string, productName: string, quantity: int, unitPrice: int}>
     * }>
     */
    public array $recentOrders;

    public int $recentOrderCount;
    public int $favoriteCount;

    public function __construct(
        #[Input] int $orderLimit,
        #[Inject] SessionInterface $session,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] OrderItemQueryInterface $orderItems,
        #[Inject] FavoriteStorageInterface $favorites,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $customer = $customerQuery->item($sessionCustomerId);
        if ($customer === null) {
            // Session points to a non-existent customer (deleted /
            // expired). Treat same as not-logged-in to avoid leaking
            // existence signal across the AAA boundary.
            throw new UnauthenticatedException();
        }

        $orders = $orderQuery->listByCustomer($sessionCustomerId, $orderLimit);

        $this->customerId = $customer->customerId;
        $this->email = $customer->email;
        $this->name01 = $customer->name01;
        $this->name02 = $customer->name02;

        $this->recentOrders = array_map(
            static fn (FinalizedOrderEntity $order): array => [
                'orderNo' => $order->orderNo,
                'total' => $order->total,
                'orderDate' => $order->orderDate,
                'orderStatus' => $order->orderStatus,
                'items' => array_map(
                    static fn (OrderItemEntity $item): array => [
                        'productCode' => $item->productCode,
                        'productName' => $item->productName,
                        'quantity' => $item->quantity,
                        'unitPrice' => $item->unitPrice,
                    ],
                    $orderItems->listByOrderNo($order->orderNo),
                ),
            ],
            $orders,
        );
        $this->recentOrderCount = count($orders);
        $this->favoriteCount = count($favorites->listByCustomer($sessionCustomerId));
    }
}
