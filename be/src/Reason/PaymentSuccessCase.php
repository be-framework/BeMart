<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason;

use MyVendor\BeMart\Be\Reason\Entity\PurchaseTotals;

/**
 * Branch case — PaymentMethod::verify() succeeded.
 *
 * Carries the PurchaseFlow totals into OrderConfirmed Final. The Final
 * delegates totals exposure to this Case (FormalStyle/CasualStyle pattern
 * from the medical-triage reference).
 */
final readonly class PaymentSuccessCase
{
    public function __construct(
        public PurchaseTotals $totals,
    ) {
    }
}
