<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderItemQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function count;
use function is_int;
use function is_string;

/**
 * Admin order fetched — Final, the back-office order detail view.
 *
 *   GetAdminOrderInput → AdminOrderFetched  (Direct, safe read)
 *
 * Aggregates three independent reads, mirroring
 * {@see AdminCustomerFetched}'s pattern (profile + orders + favorites):
 *
 *   - `OrderQuery::byOrderNo`        → the finalized-order header
 *   - `OrderQuery::itemsByOrderNo`   → the line-item snapshot
 *   - `CustomerQuery::findById`      → customer summary so the admin
 *      sees WHO placed the order (the customer's name + email — not the
 *      full profile, which has its own admin endpoint)
 *
 * AUTHZ — cross-firewall (Wave 4 lesson):
 *
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown orderNo      → OrderNotFoundException            (404)
 *
 * The admin firewall check happens before existence is probed — an
 * admin-anonymous client learns NOTHING about which orderNos resolve
 * (anti-enumeration, same discipline as AdminCustomerFetched).
 *
 * Customer existence is treated lazily: if the order's customerId
 * does NOT resolve (e.g. the customer was soft-deleted, EC-CUBE keeps
 * the row but flips the email — see {@see AdminCustomerDeleted}), the
 * `customer` projection is null. The 404 path is reserved for missing
 * ORDERS; an orphaned-customer order is still a real order.
 *
 * Public surface mirrors the ALPS `Order` resource descriptors that
 * are persisted on FinalizedOrderEntity. Shipping / OrderItem sub-
 * descriptors fold into the same flat projection; deeper fields
 * (taxRate, deliveryName, trackingNumber) are Phase 2 scope.
 *
 * Mass-assignment safety: the adminId is read exclusively from the
 * AdminSession; only the `$orderNo` is request-controlled.
 */
final readonly class AdminOrderFetched
{
    public string $orderNo;
    public string $preOrderId;
    public string $customerId;
    public int $paymentMethodId;
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

    public int $itemCount;

    /** @var array{customerId: string, email: string, name01: string, name02: string}|null */
    public array|null $customer;

    public function __construct(
        #[Input] string $orderNo,
        #[Inject] AdminSession $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] OrderItemQueryInterface $orderItems,
        #[Inject] CustomerQueryInterface $customerQuery,
    ) {
        // AUTHZ cross-firewall first — refuse non-admin requests before
        // probing existence (no enumeration via 404 vs 403 distinction).
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $order = $orderQuery->byOrderNo($orderNo);
        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $items = $orderItems->listByOrderNo($orderNo);
        $customer = $customerQuery->item($order->customerId);

        $this->orderNo = $order->orderNo;
        $this->preOrderId = $order->preOrderId;
        $this->customerId = $order->customerId;
        $this->paymentMethodId = $order->paymentMethodId;
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
            $items,
        );
        $this->itemCount = count($items);

        if ($customer === null && $order->customerSnapshot !== []) {
            $this->customer = [
                'customerId' => '',
                'email' => self::stringValue($order->customerSnapshot['email'] ?? null),
                'name01' => self::stringValue($order->customerSnapshot['name01'] ?? null),
                'name02' => self::stringValue($order->customerSnapshot['name02'] ?? null),
            ];

            return;
        }

        $this->customer = $customer === null ? null : [
            'customerId' => $customer->customerId,
            'email' => $customer->email,
            'name01' => $customer->name01,
            'name02' => $customer->name02,
        ];
    }

    private static function stringValue(mixed $value): string
    {
        return is_string($value) || is_int($value) ? (string) $value : '';
    }
}
