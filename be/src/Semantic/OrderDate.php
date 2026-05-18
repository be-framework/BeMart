<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Order date — server-derived. Set by CheckoutSettled at the moment
 * the order is finalized (ATOM-formatted ISO 8601 string). The Being
 * is the contract; this Semantic exists only so the string can flow
 * as `#[Input]` without raising a no-Semantic notice.
 */
final class OrderDate
{
    #[Validate]
    public function validate(string $orderDate): void
    {
        // Type assertion only — server-set.
    }
}
