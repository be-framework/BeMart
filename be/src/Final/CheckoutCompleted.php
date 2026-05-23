<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseTotals;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Terminal Final — proof that the checkout was committed end-to-end.
 *
 * Multi-side-effect convergence: existence of this object proves three
 * independent durable effects have run, all in this constructor:
 *
 *   1. `OrderCommandInterface::register()` — persists the FinalizedOrderEntity
 *      to dtb_order with orderStatus=NEW(1).
 *   2. `MailerInterface::sendOrderConfirmation()` — queues the order-
 *      confirmation email to the customer.
 *   3. `CartCommandInterface::clearByPreOrderId()` — removes the source Cart
 *      so the customer cannot replay the same pre-order.
 *
 * The order matters: persist FIRST (the record of truth) → mail (signals
 * to the customer that the order is recorded) → cart-clear (cleanup).
 * Mailer is non-throwing by contract (EC-CUBE treats mail-send failure as
 * non-blocking once the row is written), so the cart-clear step can rely
 * on the order being durable.
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
        );

        $orderCommand->register($finalizedOrder);
        $mailer->sendOrderConfirmation($finalizedOrder);
        $cartCommand->clearByPreOrderId($preOrderId);
    }
}
