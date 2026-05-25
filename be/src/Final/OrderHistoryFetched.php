<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function count;

/**
 * Order history fetched — Final, the logged-in customer's full past-order
 * list (the "all orders" projection).
 *
 *   GetOrderHistoryInput → OrderHistoryFetched  (Direct, safe read)
 *
 * Distinct from {@see MypageFetched}, which surfaces only the most recent
 * five rows alongside the customer's profile and favorites count for the
 * dashboard panel. This Final is the unbounded view — paged by
 * `historyLimit` + `offset` so the BEAR layer can walk a long history.
 *
 * AUTHN: customerId comes from {@see SessionInterface}. A null session
 * raises {@see UnauthenticatedException} — the BEAR layer maps this to
 * 401. Unlike {@see MypageFetched} we do not consult CustomerQuery::findById
 * here: this resource exposes only the customer's own orders (a one-table
 * read), so an existence check on the customer record would add no
 * security and would couple the resource to a second query.
 *
 * The `orders` list is a projection — not the FinalizedOrderEntity itself
 * — so the dashboard renders a flat structure and the entity's internal
 * layout (preOrderId, addPoint, usePoint, …) does not leak into the HTTP
 * body. Same convention as {@see MypageFetched::recentOrders}.
 */
final readonly class OrderHistoryFetched
{
    /** @var non-empty-string */
    public string $customerId;

    /** @var list<array{orderNo: string, total: int, paymentTotal: int, orderDate: string, paymentDate: string, orderStatus: int}> */
    public array $orders;

    public int $orderCount;
    public int $limit;
    public int $offset;

    public function __construct(
        #[Input] int $historyLimit,
        #[Input] int $offset,
        #[Inject] SessionInterface $session,
        #[Inject] OrderQueryInterface $orderQuery,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $rows = $orderQuery->listByCustomer($sessionCustomerId, $historyLimit, $offset);

        $this->customerId = $sessionCustomerId;
        $this->orders = array_map(
            static fn (FinalizedOrderEntity $order): array => [
                'orderNo' => $order->orderNo,
                'total' => $order->total,
                'paymentTotal' => $order->paymentTotal,
                'orderDate' => $order->orderDate,
                'paymentDate' => $order->paymentDate,
                'orderStatus' => $order->orderStatus,
            ],
            $rows,
        );
        $this->orderCount = count($rows);
        $this->limit = $historyLimit;
        $this->offset = $offset;
    }
}
