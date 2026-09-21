<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminCustomerDeliveryAddressCreated;

/**
 * Input for doCreateCustomerDeliveryAddress — admin adds a delivery
 * address to an arbitrary customer's book (Wave 3, customer-address-write).
 *
 *   AdminCreateCustomerDeliveryAddressInput
 *       → AdminCustomerDeliveryAddressCreated   (Direct, unsafe)
 *
 * Actor-scope inversion vs the storefront {@see CreateCustomerAddressInput}:
 * the storefront variant derives customerId from CustomerSession (the
 * logged-in customer edits their own book). The admin has no CustomerSession
 * in its firewall and edits a customer keyed by the route-param customerId,
 * so `customerId` is an explicit Input here. `addressId` is INTENTIONALLY
 * ABSENT — the Final server-generates it via AddressIdProvider, closing the
 * mass-assignment hole (a body that smuggles addressId cannot reach the
 * persisted row).
 */
#[Be(AdminCustomerDeliveryAddressCreated::class)]
final readonly class AdminCreateCustomerDeliveryAddressInput
{
    /**
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $companyName
     */
    public function __construct(
        public string $customerId,
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
