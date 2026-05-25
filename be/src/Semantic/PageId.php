<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * Page id — server-derived, provided by PageIdProvider
 * (Wave 9). Type assertion only.
 */
final class PageId
{
    #[Validate]
    public function validate(string|null $pageId): void
    {
        // Type assertion only — provider is the contract.
    }
}
