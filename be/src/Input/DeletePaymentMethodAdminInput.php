<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\PaymentMethodAdminDeleted;

/**
 * Input for doDeletePayment — admin removes a payment-method master
 * row (Wave 9θ).
 *
 *   DeletePaymentMethodAdminInput → PaymentMethodAdminDeleted
 *     (Direct, idempotent, admin AUTHZ)
 */
#[Be(PaymentMethodAdminDeleted::class)]
final readonly class DeletePaymentMethodAdminInput
{
    /**
     * @psalm-taint-source input $paymentId
     */
    public function __construct(
        public string $paymentId,
    ) {
    }
}
