<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function count;

/**
 * Admin order list fetched — Final, the back-office order grid projection.
 *
 *   GetAdminOrderListInput → AdminOrderListFetched  (Direct, safe read)
 *
 * AUTHZ — admin firewall (Wave 4 contract, same shape as Wave 5
 * {@see CustomerListFetched}):
 *   AdminSessionInterface::adminId() === null → UnauthorizedAdminAccess
 *
 * Admin-only endpoint. Distinct from the customer-side
 * {@see OrderHistoryFetched} (one customer's history); this Final
 * exposes EVERY finalized order on the system — that's the point of
 * the admin grid. Ownership AUTHZ is intentionally absent: an admin
 * who has crossed the admin firewall is permitted to see anyone's
 * order.
 *
 * Pagination: `limit` + `offset` mirror the Wave 6R OrderHistoryFetched
 * convention so the BEAR layer can walk arbitrarily-long histories.
 *
 * Public surface — shallow projection of FinalizedOrderEntity, mirroring
 * the customer-side OrderHistoryFetched::orders convention. The full
 * entity (preOrderId, addPoint, usePoint, paymentMethodId, …)
 * deliberately does NOT leak into the HTTP body; the admin grid only
 * needs the identification columns. Drill-down via goOrder (the
 * per-order detail screen) surfaces the richer view.
 */
final readonly class AdminOrderListFetched
{
    /** @var list<array{orderNo: string, customerId: string, total: int, paymentTotal: int, orderDate: string, orderStatus: int}> */
    public array $orders;

    public int $count;
    public int $limit;
    public int $offset;

    public function __construct(
        #[Input] int $limit,
        #[Input] int $offset,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $orderQuery->listAll($limit, $offset);

        $this->orders = array_map(
            static fn (FinalizedOrderEntity $order): array => [
                'orderNo' => $order->orderNo,
                'customerId' => $order->customerId,
                'total' => $order->total,
                'paymentTotal' => $order->paymentTotal,
                'orderDate' => $order->orderDate,
                'orderStatus' => $order->orderStatus,
            ],
            $rows,
        );
        $this->count = count($rows);
        $this->limit = $limit;
        $this->offset = $offset;
    }
}
