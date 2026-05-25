<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * News id — server-derived, provided by NewsIdProvider
 * (Wave 9). Type assertion only.
 */
final class NewsId
{
    #[Validate]
    public function validate(string|null $newsId): void
    {
        // Type assertion only.
    }
}
