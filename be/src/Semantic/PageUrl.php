<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Page URL path — EC-CUBE 4.3 dtb_page.url (Wave 9). Type assertion
 * only in the first iteration.
 */
final class PageUrl
{
    #[Validate]
    public function validate(string|null $pageUrl): void
    {
        // Type assertion only.
    }
}
