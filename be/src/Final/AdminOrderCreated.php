<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderItemCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\Param\OrderItemList;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\PurchaseFlowInterface;
use MyVendor\BeMart\Be\Reason\Provider\OrderNoProvider;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function count;
use function date;

/**
 * Admin order created — Final, proof an admin manually inserted a fresh
 * finalized order **with its line-item snapshot** (doCreateOrder).
 *
 *   AdminCreateOrderInput → AdminOrderCreated  (Direct, unsafe)
 *
 * AUTHZ — admin firewall:
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess (403)
 *
 * Convergence — existence of this object proves two durable effects ran
 * in order:
 *   1. `OrderCommandInterface::register()`     — the dtb_order row.
 *   2. `OrderItemCommandInterface::register()` — the dtb_order_item
 *      snapshot (one row per posted line). The order row is written
 *      FIRST so the item write can resolve the parent by orderNo.
 *
 * Totals are NOT trusted from the client. The posted line items are run
 * through the shared {@see PurchaseFlowInterface} (the same recompute
 * the storefront checkout uses) to derive `subtotal`, `tax`, the base
 * `total` and `addPoint`. The admin-supplied `deliveryFeeTotal` feeds
 * PurchaseFlow; `charge` / `discount` are applied on top of the
 * PurchaseFlow base total (EC-CUBE lets back-office staff adjust those
 * on a manual order), so `total = purchaseFlowTotal + charge - discount`
 * and `paymentTotal = total` (usePoint is fixed to 0 for data-entry
 * orders). `orderNo` is allocated server-side via {@see OrderNoProvider}
 * and `orderStatus` is fixed to NEW(1).
 */
final readonly class AdminOrderCreated
{
    public string $orderNo;
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
    public int $itemCount;
    public int $orderStatus;
    public string $orderDate;

    /**
     * @param list<array{productCode: string, productName: string, unitPrice: int, quantity: int}> $orderItems
     */
    public function __construct(
        #[Input] string $customerId,
        #[Input] int $paymentMethodId,
        #[Input] array $orderItems,
        #[Input] int $deliveryFeeTotal,
        #[Input] int $charge,
        #[Input] int $discount,
        #[Inject] AdminSession $adminSession,
        #[Inject] OrderNoProvider $orderNumbers,
        #[Inject] PurchaseFlowInterface $purchaseFlow,
        #[Inject] OrderCommandInterface $orderCommand,
        #[Inject] OrderItemCommandInterface $orderItemCommand,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $orderNo = $orderNumbers->get();
        $orderDate = date('Y-m-d H:i:s');

        // Project the posted lines into the cart shape PurchaseFlow reads
        // and the snapshot shape dtb_order_item stores — same source, two
        // projections.
        $cartItems = [];
        $snapshot = [];
        foreach ($orderItems as $line) {
            $cartItems[] = new CartItemEntity(
                productCode: $line['productCode'],
                quantity: $line['quantity'],
                price: $line['unitPrice'],
            );
            $snapshot[] = new OrderItemEntity(
                orderNo: $orderNo,
                productCode: $line['productCode'],
                productName: $line['productName'],
                quantity: $line['quantity'],
                unitPrice: $line['unitPrice'],
            );
        }

        $preOrder = new OrderEntity(
            preOrderId: $orderNo,
            customerId: $customerId,
            paymentMethodId: $paymentMethodId,
            items: $cartItems,
            deliveryFeeTotal: $deliveryFeeTotal,
        );
        $totals = $purchaseFlow->apply($preOrder);

        // PurchaseFlow owns subtotal / tax / delivery / base total /
        // points; the admin's charge & discount adjust the payable total.
        $total = $totals->total + $charge - $discount;

        $entity = new FinalizedOrderEntity(
            orderNo: $orderNo,
            preOrderId: $orderNo,
            customerId: $customerId,
            paymentMethodId: $paymentMethodId,
            subtotal: $totals->subtotal,
            deliveryFeeTotal: $totals->deliveryFeeTotal,
            charge: $charge,
            discount: $discount,
            tax: $totals->tax,
            total: $total,
            paymentTotal: $total,
            addPoint: $totals->addPoint,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: $orderDate,
            paymentDate: '',
        );

        $orderCommand->register($entity);
        $orderItemCommand->register($orderNo, OrderItemList::fromArray($snapshot));

        $this->orderNo = $entity->orderNo;
        $this->customerId = $entity->customerId;
        $this->paymentMethodId = $entity->paymentMethodId;
        $this->subtotal = $entity->subtotal;
        $this->deliveryFeeTotal = $entity->deliveryFeeTotal;
        $this->charge = $entity->charge;
        $this->discount = $entity->discount;
        $this->tax = $entity->tax;
        $this->total = $entity->total;
        $this->paymentTotal = $entity->paymentTotal;
        $this->addPoint = $entity->addPoint;
        $this->itemCount = count($snapshot);
        $this->orderStatus = $entity->orderStatus;
        $this->orderDate = $entity->orderDate;
    }
}
