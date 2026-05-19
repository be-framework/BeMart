<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminTaxRuleListFetched;

/**
 * Input for goTaxRuleList — admin lists every tax-rule master row
 * (Wave 9θ).
 *
 *   GetAdminTaxRuleListInput → AdminTaxRuleListFetched
 *     (Direct, safe read, admin AUTHZ)
 */
#[Be(AdminTaxRuleListFetched::class)]
final readonly class GetAdminTaxRuleListInput
{
    public function __construct()
    {
    }
}
