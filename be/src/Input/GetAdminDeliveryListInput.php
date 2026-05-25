<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminDeliveryListFetched;

/**
 * Input for goDeliveryList — admin lists every delivery-method master
 * row (Wave 9θ).
 *
 *   GetAdminDeliveryListInput → AdminDeliveryListFetched
 *     (Direct, safe read, admin AUTHZ)
 */
#[Be(AdminDeliveryListFetched::class)]
final readonly class GetAdminDeliveryListInput
{
    public function __construct()
    {
    }
}
