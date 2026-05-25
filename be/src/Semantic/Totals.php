<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Reason\Entity\PurchaseFlowResult;

/**
 * Totals — the computed PurchaseFlow result (subtotal / tax / charge / etc.)
 * carried forward from PurchaseFlowApplied to PaymentVerified and on to
 * OrderConfirming.
 *
 * Composite-type assertion: the PurchaseFlowResult type itself is the
 * contract; individual field constraints are guarded by Subtotal, Tax,
 * Total, etc. on their respective scalars.
 */
final class Totals
{
    #[Validate]
    public function validate(PurchaseFlowResult $totals): void
    {
        // Type assertion only — PurchaseFlowResult composite is the contract.
    }
}
