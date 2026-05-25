<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminPaymentListFetched;

/**
 * Input for goPaymentList — admin lists every payment-method master row
 * (Wave 9θ).
 *
 *   GetAdminPaymentListInput → AdminPaymentListFetched
 *     (Direct, safe read, admin AUTHZ)
 */
#[Be(AdminPaymentListFetched::class)]
final readonly class GetAdminPaymentListInput
{
    public function __construct()
    {
    }
}
