<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminLayoutListFetched;

/**
 * Input for goLayoutList (Wave 9).
 */
#[Be(AdminLayoutListFetched::class)]
final readonly class GetAdminLayoutListInput
{
    public function __construct()
    {
    }
}
