<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Generic title text for admin-maintained content rows.
 */
final class Title
{
    #[Validate]
    public function validate(string|null $title): void
    {
        // Type assertion only — context-specific forms own required/length rules.
    }
}
