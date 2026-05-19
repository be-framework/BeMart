<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminNewsListFetched;

/**
 * Input for goNewsList — admin lists news posts (Wave 9).
 */
#[Be(AdminNewsListFetched::class)]
final readonly class GetAdminNewsListInput
{
    public function __construct()
    {
    }
}
