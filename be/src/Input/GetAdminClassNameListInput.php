<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminClassNameListFetched;

/**
 * Input for goClassNameList — admin lists every product class-name axis
 * (Wave 7).
 *
 *   GetAdminClassNameListInput → AdminClassNameListFetched
 *     (Direct, safe read, admin AUTHZ)
 */
#[Be(AdminClassNameListFetched::class)]
final readonly class GetAdminClassNameListInput
{
    public function __construct()
    {
    }
}
