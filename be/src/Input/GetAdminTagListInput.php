<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminTagListFetched;

/**
 * Input for goTagList (Wave 9).
 */
#[Be(AdminTagListFetched::class)]
final readonly class GetAdminTagListInput
{
    public function __construct()
    {
    }
}
