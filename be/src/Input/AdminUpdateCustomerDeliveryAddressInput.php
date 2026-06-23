<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminCustomerDeliveryAddressUpdated;

/**
 * Input for doUpdateCustomerDeliveryAddress — admin edits one row in an
 * arbitrary customer's address book (Wave 3, customer-address-write).
 *
 *   AdminUpdateCustomerDeliveryAddressInput
 *       → AdminCustomerDeliveryAddressUpdated   (Direct, idempotent)
 *
 * Partial-update convention (mirrors the storefront
 * {@see UpdateCustomerAddressInput}): every editable field is nullable and
 * null leaves the persisted value untouched. The difference is actor scope —
 * `customerId` is the route-param Input (admin firewall), not a session-
 * derived value. `addressId` selects the target row; the Final verifies it
 * is owned by `customerId` before merging, so a tampered addressId cannot
 * move a row across customers.
 */
#[Be(AdminCustomerDeliveryAddressUpdated::class)]
final readonly class AdminUpdateCustomerDeliveryAddressInput
{
    /**
     * @psalm-taint-source input $customerId
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
        public string $customerId,
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
