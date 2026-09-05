<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\PreOrderClaimQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\Result\ClaimedOrderNo;
use Override;

/**
 * Compare-and-swap against `dtb_order`, composed from the two MediaQuery
 * statements that express it: a conditional UPDATE
 * ({@see PreOrderClaimQueryInterface::claim()}) followed by a
 * status-agnostic read of the row's order number
 * ({@see PreOrderClaimQueryInterface::claimedOrderNo()}).
 *
 * MySQL serialises the two conditional UPDATEs on the row lock, so exactly
 * one of them matches `order_status_id = 8` and the read that follows
 * reports that winner to every caller.
 */
final readonly class SqlPreOrderClaim implements PreOrderClaimInterface
{
    public function __construct(private PreOrderClaimQueryInterface $claimQuery)
    {
    }

    #[Override]
    public function claim(string $preOrderId, string $orderNo): ClaimedOrderNo
    {
        $this->claimQuery->claim($preOrderId, $orderNo);

        return $this->claimQuery->claimedOrderNo($preOrderId);
    }
}
