<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Layout id — EC-CUBE 4.3 dtb_layout.id (Wave 9).
 */
final class LayoutId
{
    #[Validate]
    public function validate(string|null $layoutId): void
    {
        // Type assertion only.
    }
}
