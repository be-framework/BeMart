<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerification as PaymentVerificationEntity;

/**
 * Payment verification outcome captured by PaymentVerified and forwarded to
 * OrderConfirming for the branching decision.
 *
 * Composite-type assertion: the PaymentVerification type itself is the
 * contract (success flag + error list).
 */
final class PaymentVerification
{
    #[Validate]
    public function validate(PaymentVerificationEntity $paymentVerification): void
    {
        // Type assertion only — PaymentVerification composite is the contract.
    }
}
