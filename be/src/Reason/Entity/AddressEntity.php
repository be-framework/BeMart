<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Customer address — projection of EC-CUBE dtb_customer_address.
 *
 * Multi-address book row owned by a single customer. The customerId
 * pins ownership for AUTHZ checks; addressId is the opaque hex handle
 * the customer uses to PUT / DELETE a specific entry. Kana fields are
 * nullable (EC-CUBE's dtb_customer_address allows null kana to mirror
 * dtb_customer's column nullability); companyName and phoneNumber are
 * also nullable per the same table shape.
 */
final readonly class AddressEntity
{
    public function __construct(
        public string $addressId,
        public string $customerId,
        public string $name01,
        public string $name02,
        public string|null $kana01,
        public string|null $kana02,
        public string|null $companyName,
        public string|null $phoneNumber,
        public string $postalCode,
        public int $pref,
        public string $addr01,
        public string $addr02,
    ) {
    }
}
