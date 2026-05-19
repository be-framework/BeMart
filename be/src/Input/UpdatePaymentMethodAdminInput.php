<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PaymentMethodAdminUpdated;

/**
 * Input for doUpdatePayment — admin edits an existing payment-method
 * master row (Wave 9θ).
 *
 *   UpdatePaymentMethodAdminInput → PaymentMethodAdminUpdated
 *     (Direct, idempotent, admin AUTHZ)
 *
 * Every editable field is optional; null = "keep current value", same
 * convention as {@see UpdateClassNameInput}.
 */
#[Be(PaymentMethodAdminUpdated::class)]
final readonly class UpdatePaymentMethodAdminInput
{
    /**
     * @psalm-taint-source input $paymentId
     * @psalm-taint-source input $paymentMethodName
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $ruleMin
     * @psalm-taint-source input $ruleMax
     * @psalm-taint-source input $visible
     */
    public function __construct(
        public string $paymentId,
        public string|null $paymentMethodName = null,
        public int|null $charge = null,
        public int|null $ruleMin = null,
        public int|null $ruleMax = null,
        public bool|null $visible = null,
    ) {
    }
}
