<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminCategoryFetched;

/**
 * Input for goCategory — admin views one catalog category (Wave 7).
 *
 *   GetAdminCategoryInput → AdminCategoryFetched (Direct, safe read)
 */
#[Be(AdminCategoryFetched::class)]
final readonly class GetAdminCategoryInput
{
    /**
     * @psalm-taint-source input $categoryId
     */
    public function __construct(
        public string $categoryId,
    ) {
    }
}
