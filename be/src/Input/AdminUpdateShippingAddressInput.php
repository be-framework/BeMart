<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminShippingAddressUpdated;

/**
 * Input for doUpdateShippingAddress — admin edits an order's shipping
 * address fields in place (Wave 9η).
 *
 *   AdminUpdateShippingAddressInput → AdminShippingAddressUpdated
 *                                      (Direct, unsafe)
 *
 * Note on actor scope: like {@see AdminSelectShippingAddressInput},
 * ALPS marks the customer-facing variant `actor-customer`; the admin-
 * side write path lands at `page://self/admin/order/shipping-address`
 * (PUT).
 *
 * If the order has no shipping address row yet (none selected), this
 * call creates one. If a row exists, it is overwritten in place.
 * Either way, the column set is fixed — the admin cannot reach
 * arbitrary dtb_shipping columns through this transition (same mass-
 * assignment discipline as {@see AdminUpdateOrderInput}).
 */
#[Be(AdminShippingAddressUpdated::class)]
final readonly class AdminUpdateShippingAddressInput
{
    /**
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $phoneNumber
     */
    public function __construct(
        public string $orderNo,
        public string $name01,
        public string $name02,
        public string $postalCode,
        public int $pref,
        public string $addr01,
        public string $addr02,
        public string $phoneNumber,
    ) {
    }
}
