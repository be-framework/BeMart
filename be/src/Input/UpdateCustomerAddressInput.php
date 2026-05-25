<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CustomerAddressUpdated;

/**
 * Input for doUpdateCustomerAddress — edit an existing row in the
 * logged-in customer's address book (Pilot 16).
 *
 * Direct pattern: Input → Final.
 *
 *   UpdateCustomerAddressInput → CustomerAddressUpdated
 *
 * Partial-update semantics (Pilot 8 convention): every editable field
 * is NULLABLE — null means "leave the current value untouched". The
 * Final merges nulls onto the current AddressEntity before writing.
 *
 * AUTHZ design — mass-assignment safety:
 *   - `customerId` is INTENTIONALLY ABSENT — pulled from the session
 *     and compared against the entity's owner; a logged-in customer
 *     cannot reassign an address to a different owner by tampering
 *     with the body
 *   - `addressId` IS in the body (the entity's primary key) — it
 *     selects which row to update, but the AUTHZ check rejects
 *     mismatched ownership with UnauthorizedAddressAccessException
 *
 * @link https://schema.org/UpdateAction
 */
#[Be(CustomerAddressUpdated::class)]
final readonly class UpdateCustomerAddressInput
{
    /**
     * @psalm-taint-source input $addressId
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $companyName
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $phoneNumber
     */
    public function __construct(
        public string $addressId,
        public string|null $name01 = null,
        public string|null $name02 = null,
        public string|null $kana01 = null,
        public string|null $kana02 = null,
        public string|null $companyName = null,
        public string|null $postalCode = null,
        public int|null $pref = null,
        public string|null $addr01 = null,
        public string|null $addr02 = null,
        public string|null $phoneNumber = null,
    ) {
    }
}
