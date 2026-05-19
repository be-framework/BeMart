<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminShippingAddressSelected;

/**
 * Input for doSelectShippingAddress — admin attaches one of the
 * order's customer's saved address-book entries to the order as the
 * delivery target (Wave 9η).
 *
 *   AdminSelectShippingAddressInput → AdminShippingAddressSelected
 *                                      (Direct, unsafe)
 *
 * Note on actor scope: ALPS surfaces this transition under
 * `actor-customer` (checkout flow). The Wave 9η iteration ALSO needs
 * an admin-side affordance — the back-office order edit screen lets
 * the admin pick a shipping address from the customer's address book
 * without having to retype every field. The customer-side renderer
 * already exists (Wave 3H {@see \MyVendor\BeMart\Resource\Page\Shopping\Shipping}
 * — a static form); this admin-side write path lands at
 * `page://self/admin/order/shipping-address`.
 *
 * Fields:
 *   - orderNo:   the target order header (Final probes
 *                {@see \MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface::byOrderNo}
 *                for existence + retrieves customerId for the
 *                address-book ownership check).
 *   - addressId: a row in the customer's address book (Final probes
 *                {@see \MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface::getById}).
 *
 * AUTHZ ladder (admin firewall → existence → ownership):
 *   1. No admin session → 403
 *   2. Unknown orderNo  → 404
 *   3. Unknown addressId / address not owned by order's customer → 404
 *      (anti-enumeration; an admin gets the same 404 whether the row
 *      is missing entirely or belongs to a different customer).
 */
#[Be(AdminShippingAddressSelected::class)]
final readonly class AdminSelectShippingAddressInput
{
    /**
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $addressId
     */
    public function __construct(
        public string $orderNo,
        public string $addressId,
    ) {
    }
}
