<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Admin payment-master id — server-derived. Provided by
 * PaymentMethodAdminIdProvider (Wave 9θ). Type assertion only
 * — the provider itself is the contract (opaque hex string).
 *
 * Distinct from {@see PaymentMethodId}, which is the legacy 1-indexed
 * integer used by the customer-side checkout factory.
 */
final class PaymentId
{
    #[Validate]
    public function validate(string|null $paymentId): void
    {
        // Type assertion only — provider is the contract.
    }
}
