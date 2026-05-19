<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedOrderAccessException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;

/**
 * Mypage history fetched — Final, the detail view of one past order
 * for the logged-in customer.
 *
 *   GetMypageHistoryInput → MypageHistoryFetched  (Direct, safe read)
 *
 * AUTHN + AUTHZ — order check sequencing (Pilot 12 lesson):
 *
 *   1. No session                  → UnauthenticatedException  (401)
 *   2. orderNo unknown             → OrderNotFoundException    (404)
 *   3. order owned by someone else → UnauthorizedOrderAccessException
 *                                                              (403)
 *
 * Anonymous requests are rejected before existence is probed (an
 * anonymous client has no business learning whether a given orderNo
 * resolves). Existence precedes AUTHZ so that the 404/403 distinction
 * is preserved for legitimate but-unauthorized callers — consistent
 * with how ReorderResolving stages the same three checks.
 *
 * `items` is exposed as a projection list (not the OrderItemEntity
 * itself) so the HTTP body shape stays flat and the entity's internal
 * field layout does not leak across the AAA boundary — same convention
 * the Mypage dashboard's `recentOrders` follows.
 */
final readonly class MypageHistoryFetched
{
    public string $orderNo;
    public string $customerId;
    public int $subtotal;
    public int $deliveryFeeTotal;
    public int $charge;
    public int $discount;
    public int $tax;
    public int $total;
    public int $paymentTotal;
    public int $addPoint;
    public int $usePoint;
    public int $orderStatus;
    public string $orderDate;
    public string $paymentDate;

    /** @var list<array{productCode: string, productName: string, quantity: int, unitPrice: int}> */
    public array $items;

    public function __construct(
        #[Input] string $orderNo,
        #[Inject] SessionInterface $session,
        #[Inject] OrderQueryInterface $orderQuery,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $order = $orderQuery->byOrderNo($orderNo);
        if (! $order instanceof FinalizedOrderEntity) {
            throw new OrderNotFoundException();
        }

        if ($order->customerId !== $sessionCustomerId) {
            throw new UnauthorizedOrderAccessException();
        }

        $this->orderNo = $order->orderNo;
        $this->customerId = $order->customerId;
        $this->subtotal = $order->subtotal;
        $this->deliveryFeeTotal = $order->deliveryFeeTotal;
        $this->charge = $order->charge;
        $this->discount = $order->discount;
        $this->tax = $order->tax;
        $this->total = $order->total;
        $this->paymentTotal = $order->paymentTotal;
        $this->addPoint = $order->addPoint;
        $this->usePoint = $order->usePoint;
        $this->orderStatus = $order->orderStatus;
        $this->orderDate = $order->orderDate;
        $this->paymentDate = $order->paymentDate;

        $this->items = array_map(
            static fn (OrderItemEntity $item): array => [
                'productCode' => $item->productCode,
                'productName' => $item->productName,
                'quantity' => $item->quantity,
                'unitPrice' => $item->unitPrice,
            ],
            $orderQuery->itemsByOrderNo($orderNo),
        );
    }
}
