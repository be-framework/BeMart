<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Provider\OrderNoProvider;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function date;

/**
 * Admin order created — Final, proof an admin manually inserted a
 * fresh finalized order (Wave 9η, **Phase 2 simplification**).
 *
 *   AdminCreateOrderInput → AdminOrderCreated  (Direct, unsafe)
 *
 * AUTHZ — admin firewall:
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess (403)
 *
 * Phase 2 scope (explicitly deferred):
 *   - PurchaseFlow recompute (tax / delivery / stock allocation)
 *   - PaymentMethod::verify() handshake
 *   - Order-item snapshot rows
 *   - Cart linkage (preOrderId placeholder used here)
 *
 * The Final allocates an orderNo via the existing
 * {@see OrderNoProvider}, derives `total` /
 * `paymentTotal` from the supplied money columns, fixes
 * `orderStatus = NEW(1)`, and persists via the existing
 * {@see OrderCommandInterface::register}. addPoint/usePoint default
 * to 0 — admins typically do not award/spend points on data-entry
 * orders, and exposing the knobs is Phase 2 scope.
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
    public int $orderStatus;
    public string $orderDate;

    public function __construct(
        #[Input] string $customerId,
        #[Input] int $paymentMethodId,
        #[Input] int $subtotal,
        #[Input] int $deliveryFeeTotal,
        #[Input] int $charge,
        #[Input] int $discount,
        #[Input] int $tax,
        #[Inject] AdminSession $adminSession,
        #[Inject] OrderNoProvider $orderNumbers,
        #[Inject] OrderCommandInterface $orderCommand,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $orderNo = $orderNumbers->get();
        $total = $subtotal + $deliveryFeeTotal + $charge + $tax - $discount;
        $orderDate = date('Y-m-d H:i:s');

        $entity = new FinalizedOrderEntity(
            orderNo: $orderNo,
            preOrderId: $orderNo,
            customerId: $customerId,
            paymentMethodId: $paymentMethodId,
            subtotal: $subtotal,
            deliveryFeeTotal: $deliveryFeeTotal,
            charge: $charge,
            discount: $discount,
            tax: $tax,
            total: $total,
            paymentTotal: $total,
            addPoint: 0,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: $orderDate,
            paymentDate: '',
        );

        $orderCommand->register($entity);

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
        $this->orderStatus = $entity->orderStatus;
        $this->orderDate = $entity->orderDate;
    }
}
