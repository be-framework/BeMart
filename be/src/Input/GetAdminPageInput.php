<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminPageFetched;

/**
 * Input for goPage — admin views one CMS page (Wave 9).
 *
 *   GetAdminPageInput → AdminPageFetched (Direct, safe read)
 */
#[Be(AdminPageFetched::class)]
final readonly class GetAdminPageInput
{
    /**
     * @psalm-taint-source input $pageId
     */
    public function __construct(
        public string $pageId,
    ) {
    }
}
