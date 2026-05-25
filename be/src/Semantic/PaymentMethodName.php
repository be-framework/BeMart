<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Payment method display name (Wave 9θ).
 *
 * Free-form, presentation-only. Type assertion plus null-passthrough
 * for partial-update flows — same convention as {@see Charge}.
 */
final class PaymentMethodName
{
    #[Validate]
    public function validate(string|null $paymentMethodName): void
    {
        // Type assertion only.
    }
}
