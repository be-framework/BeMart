<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * External link URL on a news entry — EC-CUBE 4.3 dtb_news.url (Wave 9).
 */
final class NewsUrl
{
    #[Validate]
    public function validate(string|null $newsUrl): void
    {
        // Type assertion only.
    }
}
