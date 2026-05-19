<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Page display name — EC-CUBE 4.3 dtb_page.name (Wave 9). The
 * in-memory iteration only asserts the type. Phase 2 may add a
 * length / trim guard once a real consumer requires it.
 */
final class PageName
{
    #[Validate]
    public function validate(string|null $pageName): void
    {
        // Type assertion only.
    }
}
