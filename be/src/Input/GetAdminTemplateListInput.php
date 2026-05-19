<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminTemplateListFetched;

/**
 * Input for goTemplateList (Wave 9).
 */
#[Be(AdminTemplateListFetched::class)]
final readonly class GetAdminTemplateListInput
{
    public function __construct()
    {
    }
}
