<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Initial point — server-derived. Granted at registration time per
 * shop config (CustomerInitialPointInterface). The config service
 * is the contract; this Semantic exists only so the int can flow
 * as `#[Input]` without raising a no-Semantic notice.
 */
final class InitialPoint
{
    #[Validate]
    public function validate(int $initialPoint): void
    {
        // Type assertion only — config service is the contract.
    }
}
