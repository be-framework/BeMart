<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * News publish date — EC-CUBE 4.3 dtb_news.publish_date (Wave 9).
 * Stored as ISO-8601 string in the in-memory iteration.
 */
final class PublishDate
{
    #[Validate]
    public function validate(string|null $publishDate): void
    {
        // Type assertion only.
    }
}
