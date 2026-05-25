<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Customer status — mtb_customer_status code. The Being that produces
 * this value is the contract (Pilot 4's CustomerRegistering hard-codes
 * 2 = Active; a future Branching Pilot will introduce 1 = Provisional
 * for the email-verify flow). This Semantic exists only so the int
 * can flow as `#[Input]` without raising a no-Semantic notice.
 */
final class CustomerStatus
{
    #[Validate]
    public function validate(int $customerStatus): void
    {
        // Type assertion only — server-set.
    }
}
