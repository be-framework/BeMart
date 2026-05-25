<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PaymentMethodAdminCreated;

/**
 * Input for doCreatePayment — admin adds a new payment-method master
 * row (Wave 9θ).
 *
 *   CreatePaymentMethodAdminInput → PaymentMethodAdminCreated
 *     (Direct, admin AUTHZ)
 *
 * `paymentId` is server-generated; the body only carries the
 * editable fields.
 */
#[Be(PaymentMethodAdminCreated::class)]
final readonly class CreatePaymentMethodAdminInput
{
    /**
     * @psalm-taint-source input $paymentMethodName
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $ruleMin
     * @psalm-taint-source input $ruleMax
     * @psalm-taint-source input $visible
     */
    public function __construct(
        public string $paymentMethodName,
        public int $charge = 0,
        public int|null $ruleMin = null,
        public int|null $ruleMax = null,
        public bool $visible = true,
    ) {
    }
}
