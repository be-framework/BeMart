<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Payment date — server-derived. Set by CheckoutSettled at the moment
 * PaymentGateway::checkout() returns success (ATOM-formatted ISO 8601
 * string). The Being is the contract; this Semantic exists only so
 * the string can flow as `#[Input]` without raising a no-Semantic
 * notice.
 */
final class PaymentDate
{
    #[Validate]
    public function validate(string $paymentDate): void
    {
        // Type assertion only — server-set.
    }
}
