<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Layout display name — EC-CUBE 4.3 dtb_layout.layout_name (Wave 9).
 */
final class LayoutName
{
    #[Validate]
    public function validate(string|null $layoutName): void
    {
        // Type assertion only.
    }
}
