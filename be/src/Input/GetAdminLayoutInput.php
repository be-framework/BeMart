<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminLayoutFetched;

/**
 * Input for goLayout — admin views one CMS layout.
 */
#[Be(AdminLayoutFetched::class)]
final readonly class GetAdminLayoutInput
{
    /**
     * @psalm-taint-source input $layoutId
     */
    public function __construct(
        public string $layoutId,
    ) {
    }
}
