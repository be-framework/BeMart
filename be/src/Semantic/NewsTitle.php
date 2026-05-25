<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * News headline — EC-CUBE 4.3 dtb_news.title (Wave 9).
 */
final class NewsTitle
{
    #[Validate]
    public function validate(string|null $newsTitle): void
    {
        // Type assertion only.
    }
}
