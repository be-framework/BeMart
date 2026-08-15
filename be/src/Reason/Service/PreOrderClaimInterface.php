<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\ClaimedOrderNo;

/**
 * Decides which request gets to complete a pre-order.
 *
 * Reading a pre-order as PROCESSING does not entitle the reader to
 * complete it — two concurrent checkouts can both hold that proof. This
 * port performs the compare-and-swap that does entitle exactly one of
 * them, and reports the winner so the losers can stop before charging a
 * card, registering line items or mailing a confirmation.
 *
 * A separate port rather than a `#[DbQuery]` method because the verdict
 * needs both a conditional write and a read of its effect; static Fake
 * fixtures cannot express that, so the Fake context supplies its own
 * in-memory implementation.
 */
interface PreOrderClaimInterface
{
    /**
     * Stamp `$orderNo` on the pre-order if it is still unclaimed, then
     * report whose number is on it.
     */
    public function claim(string $preOrderId, string $orderNo): ClaimedOrderNo;
}
