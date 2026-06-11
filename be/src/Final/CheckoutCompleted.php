<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseTotals;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderItemCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\Param\OrderItemList;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;

/**
 * Terminal Final — proof that the checkout was committed end-to-end.
 *
 * Multi-side-effect convergence: existence of this object proves four
 * independent durable effects have run, all in this constructor:
 *
 *   1. `OrderCommandInterface::register()` — persists the FinalizedOrderEntity
 *      to dtb_order with orderStatus=NEW(1).
 *   2. `OrderItemCommandInterface::register()` — freezes the order-time
 *      line-item snapshot into dtb_order_item (one row per cart line).
 *      EC-CUBE captures this snapshot so later catalog edits never rewrite
 *      a past receipt; the display name is resolved from
 *      {@see ProductClassQueryInterface} (the pre-order's bare
 *      `CartItemEntity` rows carry productCode only), exactly as
 *      {@see OrderConfirmed} composes the confirm screen. The same write
 *      surface backs the back-office {@see AdminOrderCreated}.
 *   3. `MailerInterface::sendOrderConfirmation()` — queues the order-
 *      confirmation email to the customer.
 *   4. `CartCommandInterface::clearByPreOrderId()` — removes the source Cart
 *      so the customer cannot replay the same pre-order.
 *
 * The order matters: persist the order FIRST (the record of truth) →
 * snapshot its items (resolves the parent by the just-written orderNo) →
 * mail (signals to the customer that the order is recorded) → cart-clear
 * (cleanup, and only after the snapshot has frozen the items the cart
 * held). Mailer is non-throwing by contract (EC-CUBE treats mail-send
 * failure as non-blocking once the row is written), so the cart-clear
 * step can rely on the order being durable.
 *
 * The public surface mirrors ALPS `ShoppingComplete` descriptors. The
 * `completeMessage` field is intentionally empty in Pilot 5 — EC-CUBE
 * lets payment plugins append to it via `appendCompleteMessage()`, but
 * the Fake gateway is plain and contributes nothing. A future Plugin
 * Pilot will wire that up.
 */
final readonly class CheckoutCompleted
{
    public string $orderNo;
    public string $completeMessage;
    public string $customerId;
    public int $total;
    public int $paymentTotal;
    public int $addPoint;
    public int $orderStatus;
    public string $orderDate;
    public string $paymentDate;

    public function __construct(
        #[Input] string $preOrderId,
        #[Input] OrderEntity $order,
        #[Input] PurchaseTotals $totals,
        #[Input] string $orderNo,
        #[Input] string $orderDate,
        #[Input] string $paymentDate,
        #[Inject] OrderCommandInterface $orderCommand,
        #[Inject] OrderItemCommandInterface $orderItemCommand,
        #[Inject] ProductClassQueryInterface $productClasses,
        #[Inject] MailerInterface $mailer,
        #[Inject] CartCommandInterface $cartCommand,
    ) {
        $this->orderNo = $orderNo;
        $this->completeMessage = '';
        $this->customerId = $order->customerId;
        $this->total = $totals->total;
        $this->paymentTotal = $totals->paymentTotal;
        $this->addPoint = $totals->addPoint;
        $this->orderStatus = FinalizedOrderEntity::STATUS_NEW;
        $this->orderDate = $orderDate;
        $this->paymentDate = $paymentDate;

        $finalizedOrder = new FinalizedOrderEntity(
            orderNo: $orderNo,
            preOrderId: $preOrderId,
            customerId: $order->customerId,
            paymentMethodId: $order->paymentMethodId,
            subtotal: $totals->subtotal,
            deliveryFeeTotal: $totals->deliveryFeeTotal,
            charge: $totals->charge,
            discount: $totals->discount,
            tax: $totals->tax,
            total: $totals->total,
            paymentTotal: $totals->paymentTotal,
            addPoint: $totals->addPoint,
            usePoint: $totals->usePoint,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: $orderDate,
            paymentDate: $paymentDate,
            customerSnapshot: $order->customerSnapshot,
        );

        // Freeze the order-time line-item snapshot. The display name is
        // resolved from the product-class read (the pre-order's items are
        // bare productCode/quantity/price), falling back to any name the
        // upstream read already carried — the same projection OrderConfirmed
        // uses for the confirm screen.
        $snapshot = array_map(
            function (CartItemEntity $item) use ($productClasses, $orderNo): OrderItemEntity {
                $productClass = $productClasses->item($item->productCode);

                return new OrderItemEntity(
                    orderNo: $orderNo,
                    productCode: $item->productCode,
                    productName: $productClass?->productName ?? $item->productName,
                    quantity: $item->quantity,
                    unitPrice: $item->price,
                );
            },
            $order->items,
        );

        $orderCommand->register($finalizedOrder);
        $orderItemCommand->register($orderNo, OrderItemList::fromArray($snapshot));
        $mailer->sendOrderConfirmation($finalizedOrder);
        $cartCommand->clearByPreOrderId($preOrderId);
    }
}
