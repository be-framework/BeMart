<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin order status updated — Final, proof an admin flipped one
 * finalized order's status column.
 *
 *   AdminUpdateOrderStatusInput → AdminOrderStatusUpdated
 *                                   (Direct, idempotent)
 *
 * AUTHZ — cross-firewall (Wave 4 lesson, same ladder as
 * {@see AdminOrderUpdated}):
 *
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown orderNo      → OrderNotFoundException            (404)
 *
 * Input validation (Wave 7 introduction): the `orderStatus` parameter
 * is bounded by the {@see \MyVendor\BeMart\Be\Semantic\OrderStatus}
 * Semantic — values outside EC-CUBE's dtb_order_status set (1, 3-9)
 * never reach this constructor (a SemanticVariableException fires at
 * the framework boundary). The Symfony Workflow transition map
 * (cancel 1,4,6→3, ship 1,6,4→5, …) is NOT enforced here — Phase 2
 * scope, the admin panel is trusted with arbitrary in-set flips for
 * now. The audit trail is what surfaces the discipline gap.
 *
 * Idempotency (ALPS `type=idempotent`): when the supplied
 * `orderStatus` equals the persisted value, the Final short-circuits
 * — no second `updateStatus` call, `changed=false` flag set, same
 * shape returned. Mirrors AdminCustomerDeleted's `alreadyDeleted`
 * pattern (Wave 6).
 *
 * Mass-assignment safety: the adminId comes from the AdminSession;
 * only `$orderNo` (target selector) and `$orderStatus` (new value)
 * are request-controlled. No way to reach other dtb_order columns
 * through this transition.
 */
final readonly class AdminOrderStatusUpdated
{
    public string $orderNo;
    public int $previousStatus;
    public int $orderStatus;
    public bool $changed;

    public function __construct(
        #[Input] string $orderNo,
        #[Input] int $orderStatus,
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

        $previous = $current->orderStatus;

        if ($previous === $orderStatus) {
            // Idempotent replay — same status, no write.
            $this->orderNo = $current->orderNo;
            $this->previousStatus = $previous;
            $this->orderStatus = $orderStatus;
            $this->changed = false;

            return;
        }

        $orderCommand->updateStatus($orderNo, $orderStatus);

        $this->orderNo = $current->orderNo;
        $this->previousStatus = $previous;
        $this->orderStatus = $orderStatus;
        $this->changed = true;
    }
}
