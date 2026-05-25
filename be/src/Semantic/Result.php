<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Reason\Entity\PaymentVerifyResult;

/**
 * Result — the PaymentMethod::verify() outcome captured by PaymentVerified
 * and forwarded to OrderConfirming for the Branching decision.
 *
 * Composite-type assertion: the PaymentVerifyResult type itself is the
 * contract (success flag + error list).
 */
final class Result
{
    #[Validate]
    public function validate(PaymentVerifyResult $result): void
    {
        // Type assertion only — PaymentVerifyResult composite is the contract.
    }
}
