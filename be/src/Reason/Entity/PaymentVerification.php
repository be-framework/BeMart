<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Outcome of PaymentMethod::verify() — EC-CUBE PaymentResult slimmed to the
 * decision the Cascade actually branches on.
 *
 * success=true  ⇒ PaymentVerified becomes PaymentSuccessCase  → OrderConfirmed
 * success=false ⇒ PaymentVerified becomes PaymentFailureCase  → OrderConfirmFailed
 *
 * `errors` carries the human-readable reasons reported by the payment plugin
 * when success is false; empty otherwise.
 */
final readonly class PaymentVerification
{
    /** @param list<string> $errors */
    public function __construct(
        public bool $success,
        public array $errors = [],
    ) {
    }
}
