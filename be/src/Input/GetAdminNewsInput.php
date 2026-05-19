<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminNewsFetched;

/**
 * Input for goNews — admin views one news post (Wave 9).
 */
#[Be(AdminNewsFetched::class)]
final readonly class GetAdminNewsInput
{
    /**
     * @psalm-taint-source input $newsId
     */
    public function __construct(
        public string $newsId,
    ) {
    }
}
