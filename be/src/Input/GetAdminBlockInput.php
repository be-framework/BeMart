<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminBlockFetched;

/**
 * Input for goBlock — admin views one CMS block (Wave 9).
 */
#[Be(AdminBlockFetched::class)]
final readonly class GetAdminBlockInput
{
    /**
     * @psalm-taint-source input $blockId
     */
    public function __construct(
        public string $blockId,
    ) {
    }
}
