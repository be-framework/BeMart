<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\OrderStatusFormatException;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;

use function in_array;

/**
 * Order status (受注ステータス) — the integer column EC-CUBE writes into
 * dtb_order.order_status_id. Wave 7 (doUpdateOrderStatus) introduces this
 * Semantic so an admin's status-flip request is bounded to the recognised
 * set before any state-machine decision runs in the Final.
 *
 * Allowed values mirror the ALPS `orderStatus` descriptor verbatim:
 *   1=新規受付 / 3=注文取消 / 4=対応中 / 5=発送済み / 6=入金済み /
 *   7=決済処理中 / 8=購入処理中 / 9=返品
 *
 * Note that "2" (formerly 入金待ち) is NOT in the set — EC-CUBE 4.x
 * removed it. Status `7` and `8` are PurchaseFlow-internal: while they
 * pass this format check, the admin flow's transition map (Symfony
 * Workflow in EC-CUBE) refuses them at the application layer. The
 * Semantic enforces only the format — not the per-transition reachability
 * (that is a domain concern owned by the Final).
 */
final class OrderStatus
{
    /** @var list<int> */
    public const ALLOWED = [
        FinalizedOrderEntity::STATUS_NEW,         // 1
        FinalizedOrderEntity::STATUS_CANCEL,      // 3
        FinalizedOrderEntity::STATUS_IN_PROGRESS, // 4
        FinalizedOrderEntity::STATUS_DELIVERED,   // 5
        FinalizedOrderEntity::STATUS_PAID,        // 6
        FinalizedOrderEntity::STATUS_PENDING,     // 7
        FinalizedOrderEntity::STATUS_PROCESSING,  // 8
        FinalizedOrderEntity::STATUS_RETURNED,    // 9
    ];

    #[Validate]
    public function validate(int $orderStatus): void
    {
        if (! in_array($orderStatus, self::ALLOWED, true)) {
            throw new OrderStatusFormatException();
        }
    }
}
