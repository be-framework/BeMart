<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CustomerAddressDeleted;

/**
 * Input for doDeleteCustomerAddress — remove a row from the
 * logged-in customer's address book (Pilot 16).
 *
 * Direct pattern: Input → Final.
 *
 *   DeleteCustomerAddressInput → CustomerAddressDeleted
 *
 * AUTHZ design — same as the update transition: `customerId` is
 * absent from the body, taken from the session and compared against
 * the entity's owner. A mismatch surfaces as
 * UnauthorizedAddressAccessException (403); a missing addressId
 * surfaces as AddressNotFoundException (404) — even on DELETE.
 *
 * The 404-on-miss decision (rather than silent 200) mirrors Pilot 11
 * doRemoveCartItem's boundary: the legitimate caller (already AUTHN'd
 * by Session) deserves to learn that the id is bogus rather than
 * being told "OK, no change" forever.
 *
 * @link https://schema.org/DeleteAction
 */
#[Be(CustomerAddressDeleted::class)]
final readonly class DeleteCustomerAddressInput
{
    /**
     * @psalm-taint-source input $addressId
     */
    public function __construct(
        public string $addressId,
    ) {
    }
}
