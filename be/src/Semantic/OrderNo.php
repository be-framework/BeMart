<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Order number — server-derived. Provided by
 * OrderNoProvider (Pilot 5 fake uses
 * bin2hex(random_bytes(16)) → 32-char opaque hex). The provider is
 * the contract; this Semantic exists only so the string can flow as
 * `#[Input]` without raising a no-Semantic notice.
 */
final class OrderNo
{
    #[Validate]
    public function validate(string $orderNo): void
    {
        // Type assertion only — provider is the contract.
    }
}
