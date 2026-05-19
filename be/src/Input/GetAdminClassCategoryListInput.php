<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminClassCategoryListFetched;

/**
 * Input for goClassCategoryList — admin lists every product variant
 * value (Wave 7).
 *
 *   GetAdminClassCategoryListInput → AdminClassCategoryListFetched
 *     (Direct, safe read, admin AUTHZ)
 *
 * Optional `classNameId` filter narrows the list to one axis; null
 * returns every row.
 */
#[Be(AdminClassCategoryListFetched::class)]
final readonly class GetAdminClassCategoryListInput
{
    /**
     * @psalm-taint-source input $classNameId
     */
    public function __construct(
        public string|null $classNameId = null,
    ) {
    }
}
