<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminBlockListFetched;

/**
 * Input for goBlockList — admin lists CMS blocks (Wave 9).
 */
#[Be(AdminBlockListFetched::class)]
final readonly class GetAdminBlockListInput
{
    public function __construct()
    {
    }
}
