<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason;

/**
 * Branch case — PaymentMethod::verify() failed.
 *
 * Carries the verifier's error messages into OrderConfirmFailed Final.
 * In EC-CUBE the controller calls $this->addError($error) and redirects
 * to ShoppingError; here the Final exposes them as a structured list.
 */
final readonly class PaymentFailureCase
{
    /** @param list<string> $errors */
    public function __construct(
        public array $errors,
    ) {
    }
}
