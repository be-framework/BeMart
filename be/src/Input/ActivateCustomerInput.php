<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CustomerActivated;

/**
 * Input for doActivateCustomer — Pilot 7.
 *
 * Direct pattern (hello-world demo): Input → Final, no Being. The
 * Final's constructor looks up the provisional customer by secretKey
 * and activates them. Idempotent: re-activating a customer who is
 * already active leaves them unchanged.
 *
 *   ActivateCustomerInput → CustomerActivated (Final)
 *
 * @link https://schema.org/ActivateAction
 */
#[Be(CustomerActivated::class)]
final readonly class ActivateCustomerInput
{
    /**
     * @psalm-taint-source input $secretKey
     */
    public function __construct(
        public string $secretKey,
    ) {
    }
}
