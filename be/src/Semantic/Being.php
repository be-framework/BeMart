<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Reason\PaymentFailureCase;
use MyVendor\BeMart\Be\Reason\PaymentSuccessCase;

/**
 * Being — the typed Branching discriminator on OrderConfirming.
 *
 * The variable holds whichever Case the upstream chain produced; the Be
 * Framework selects the matching Final by type. Validation here is a type
 * gate only — the Cases themselves carry the payload contracts.
 */
final class Being
{
    #[Validate]
    public function validate(PaymentSuccessCase|PaymentFailureCase $being): void
    {
        // Type assertion only — the Case classes are the contracts.
    }
}
