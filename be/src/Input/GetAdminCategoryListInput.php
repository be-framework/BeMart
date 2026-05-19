<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminCategoryListFetched;

/**
 * Input for goCategoryList — admin lists every catalog category
 * (Wave 7).
 *
 * Direct pattern: no inputs other than the implicit admin session,
 * which the Final pulls from {@see \MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface}.
 * The first iteration returns a flat list; nested-children projection
 * is deferred to Phase 2.
 *
 *   GetAdminCategoryListInput → AdminCategoryListFetched (Direct, safe read)
 */
#[Be(AdminCategoryListFetched::class)]
final readonly class GetAdminCategoryListInput
{
    public function __construct()
    {
    }
}
