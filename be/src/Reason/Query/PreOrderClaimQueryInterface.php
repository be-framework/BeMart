<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\ClaimedOrderNo;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * The two statements that make up the checkout claim, kept on their own
 * port so the SQL adapter can compose them
 * ({@see \MyVendor\BeMart\Be\Reason\Service\SqlPreOrderClaim}) without
 * every OrderCommand/OrderQuery implementation having to model a
 * compare-and-swap it cannot express.
 */
interface PreOrderClaimQueryInterface
{
    /**
     * Stamp `$orderNo` on the pre-order and flip it out of PROCESSING —
     * but only while it still IS in PROCESSING. A losing caller's
     * statement matches no row.
     */
    #[DbQuery('order_claim_pre_order')]
    public function claim(string $preOrderId, string $orderNo): void;

    /**
     * The order number now on the pre-order's row, whatever its status.
     * Status-agnostic on purpose: after a successful claim the row is no
     * longer PROCESSING, so the pre-order read can no longer see it.
     */
    #[DbQuery('order_claimed_no')]
    public function claimedOrderNo(string $preOrderId): ClaimedOrderNo;
}
