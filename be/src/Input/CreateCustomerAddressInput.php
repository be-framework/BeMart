<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CustomerAddressCreated;

/**
 * Input for doCreateCustomerAddress — add a new entry to the
 * logged-in customer's address book (Pilot 16).
 *
 * Direct pattern: Input → Final.
 *
 *   CreateCustomerAddressInput → CustomerAddressCreated
 *
 * AUTHZ design — mass-assignment safety (Pilot 5 F-2 + Pilot 8
 * lesson, carried by Pilot 13):
 *   - `addressId` is INTENTIONALLY ABSENT — generated server-side by
 *     AddressIdGeneratorInterface so the client cannot collide with
 *     or overwrite existing rows
 *   - `customerId` is INTENTIONALLY ABSENT — taken from the session
 *     so the client cannot pin a new row onto someone else
 *
 * Required fields mirror ALPS doCreateCustomerAddress.descriptor[]:
 * name01, name02, postalCode, pref, addr01, addr02, phoneNumber.
 * Kana and companyName are optional (nullable) to mirror EC-CUBE's
 * dtb_customer_address column nullability.
 *
 * @link https://schema.org/CreateAction
 */
#[Be(CustomerAddressCreated::class)]
final readonly class CreateCustomerAddressInput
{
    /**
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
        public string $name01,
        public string $name02,
        public string $postalCode,
        public int $pref,
        public string $addr01,
        public string $addr02,
        public string $phoneNumber,
        public string|null $kana01 = null,
        public string|null $kana02 = null,
        public string|null $companyName = null,
    ) {
    }
}
