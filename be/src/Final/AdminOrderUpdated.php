<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin order updated — Final, proof an admin edited one order in place.
 *
 *   AdminUpdateOrderInput → AdminOrderUpdated  (Direct, idempotent)
 *
 * AUTHZ — cross-firewall (Wave 4 lesson, same ladder as Wave 5
 * AdminCustomerFetched / Wave 6 AdminCustomerDeleted):
 *
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown orderNo      → OrderNotFoundException            (404)
 *
 * The admin firewall check happens before existence is probed so an
 * admin-anonymous client has no business learning whether a given
 * orderNo resolves.
 *
 * Merge semantics (Pilot 8 partial-update convention, mirrored by
 * Pilot 16 CustomerAddressUpdated):
 *
 *   - discount  null → keep persisted value, else overwrite
 *   - charge    null → keep persisted value, else overwrite
 *   - usePoint  null → keep persisted value, else overwrite
 *
 * Mass-assignment safety (Pilot 5 F-2 lesson) — the editable surface
 * is INTENTIONALLY narrow. Non-editable fields are reused verbatim
 * from the persisted entity, so a malicious client cannot reach
 * `customerId` / `total` / `orderStatus` / `orderDate` etc. via this
 * transition (`doUpdateOrderStatus` is the dedicated channel for the
 * status column).
 *
 * Idempotency (ALPS `type=idempotent`): a PUT with the same body
 * returns the same projection. We do not short-circuit on equality
 * (no `changed` flag here) — the operation is the same overwrite
 * either way and observers can compare the projected fields against
 * what they sent. The status-flip flow ({@see AdminOrderStatusUpdated})
 * DOES surface a `changed` flag because the audit case is different
 * (status changes are workflow-significant).
 */
final readonly class AdminOrderUpdated
{
    public string $orderNo;
    public string $customerId;
    public int $discount;
    public int $charge;
    public int $usePoint;
    public int $subtotal;
    public int $deliveryFeeTotal;
    public int $tax;
    public int $total;
    public int $paymentTotal;
    public int $orderStatus;

    public function __construct(
        #[Input] string $orderNo,
        #[Input] int|null $discount,
        #[Input] int|null $charge,
        #[Input] int|null $usePoint,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] OrderCommandInterface $orderCommand,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $orderQuery->byOrderNo($orderNo);
        if ($current === null) {
            throw new OrderNotFoundException();
        }

        // Merge nulls onto the persisted row — only the three editable
        // fields participate. Every other field is reused verbatim so
        // the entity round-trips through the command unchanged.
        $merged = new FinalizedOrderEntity(
            orderNo: $current->orderNo,
            preOrderId: $current->preOrderId,
            customerId: $current->customerId,
            paymentMethodId: $current->paymentMethodId,
            subtotal: $current->subtotal,
            deliveryFeeTotal: $current->deliveryFeeTotal,
            charge: $charge ?? $current->charge,
            discount: $discount ?? $current->discount,
            tax: $current->tax,
            total: $current->total,
            paymentTotal: $current->paymentTotal,
            addPoint: $current->addPoint,
            usePoint: $usePoint ?? $current->usePoint,
            orderStatus: $current->orderStatus,
            orderDate: $current->orderDate,
            paymentDate: $current->paymentDate,
        );

        $orderCommand->update($merged);

        $this->orderNo = $merged->orderNo;
        $this->customerId = $merged->customerId;
        $this->discount = $merged->discount;
        $this->charge = $merged->charge;
        $this->usePoint = $merged->usePoint;
        $this->subtotal = $merged->subtotal;
        $this->deliveryFeeTotal = $merged->deliveryFeeTotal;
        $this->tax = $merged->tax;
        $this->total = $merged->total;
        $this->paymentTotal = $merged->paymentTotal;
        $this->orderStatus = $merged->orderStatus;
    }
}
