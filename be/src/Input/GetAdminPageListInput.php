<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminPageListFetched;

/**
 * Input for goPageList — admin lists every CMS page (Wave 9 CMS slice).
 *
 *   GetAdminPageListInput → AdminPageListFetched (Direct, safe read,
 *                                                 admin AUTHZ)
 */
#[Be(AdminPageListFetched::class)]
final readonly class GetAdminPageListInput
{
    public function __construct()
    {
    }
}
