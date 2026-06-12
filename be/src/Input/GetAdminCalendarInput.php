<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminCalendarFetched;

#[Be(AdminCalendarFetched::class)]
final readonly class GetAdminCalendarInput
{
    public function __construct()
    {
    }
}
