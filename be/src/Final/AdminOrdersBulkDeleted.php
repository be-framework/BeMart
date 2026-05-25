<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function count;

/**
 * Admin orders bulk-deleted — Final, proof an admin cancelled one or
 * more finalized orders in a single call.
 *
 *   AdminBulkDeleteOrderInput → AdminOrdersBulkDeleted  (Direct, unsafe)
 *
 * AUTHZ — admin firewall (same ladder as the rest of Wave 7+):
 *   AdminSessionInterface::adminId() === null → UnauthorizedAdminAccess (403)
 *
 * Unknown orderNos in the list are silently skipped — the public
 * `requestedCount` / `changedCount` projection lets the admin UI
 * surface anomalies. Mirrors the Wave 8 product-bulk pattern.
 *
 * Idempotency note: rerunning against orders already at
 * `STATUS_CANCEL(3)` does NOT count toward `changedCount`. So a
 * `changedCount=0` outcome is valid when every targeted order was
 * already cancelled — a replay is safe.
 */
final readonly class AdminOrdersBulkDeleted
{
    /** @var list<string> */
    public array $orderNos;
    public int $requestedCount;
    public int $changedCount;

    /** @param list<string> $orderNos */
    public function __construct(
        #[Input] array $orderNos,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] OrderCommandInterface $orderCommand,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $changed = 0;
        foreach ($orderNos as $orderNo) {
            $current = $orderQuery->byOrderNo($orderNo);
            if ($current === null) {
                continue;
            }

            if ($current->orderStatus === FinalizedOrderEntity::STATUS_CANCEL) {
                continue;
            }

            $orderCommand->updateStatus($orderNo, FinalizedOrderEntity::STATUS_CANCEL);
            $changed++;
        }

        $this->orderNos = $orderNos;
        $this->requestedCount = count($orderNos);
        $this->changedCount = $changed;
    }
}
