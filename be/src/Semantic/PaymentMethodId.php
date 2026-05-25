<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\PaymentMethodIdFormatException;

/**
 * Payment method identifier — EC-CUBE 4.3 dtb_payment.id.
 *
 * Validated as a positive integer. Lower bound is locked at 1 because
 * dtb_payment ids are 1-indexed; the upper bound is intentionally
 * left open so new payment methods added to master data do not break
 * domain code.
 */
final class PaymentMethodId
{
    #[Validate]
    public function validate(int $paymentMethodId): void
    {
        if ($paymentMethodId < 1) {
            throw new PaymentMethodIdFormatException();
        }
    }
}
