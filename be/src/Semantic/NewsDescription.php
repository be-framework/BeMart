<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * News body — EC-CUBE 4.3 dtb_news.description (Wave 9). HTML purifier
 * pass is Phase 2 scope.
 */
final class NewsDescription
{
    #[Validate]
    public function validate(string|null $newsDescription): void
    {
        // Type assertion only.
    }
}
