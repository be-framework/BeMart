<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * News link open-method flag — EC-CUBE 4.3 dtb_news.link_method (Wave 9).
 * Boolean: false = same window, true = new window (target="_blank").
 */
final class LinkMethod
{
    #[Validate]
    public function validate(bool|null $linkMethod): void
    {
        // Type assertion only.
    }
}
