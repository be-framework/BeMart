<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Tag display name — EC-CUBE 4.3 dtb_tag.name (Wave 9).
 */
final class TagName
{
    #[Validate]
    public function validate(string|null $tagName): void
    {
        // Type assertion only.
    }
}
