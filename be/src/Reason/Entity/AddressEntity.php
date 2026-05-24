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
 *
 * Phase 3 enrichment — `prefName` carries the prefecture's DISPLAY name
 * (`mtb_pref.name`), so the address-book screen (EC-CUBE
 * `Mypage/delivery.twig`) can render the prefecture name rather than the
 * bare integer `pref` master id. It is the LAST, OPTIONAL constructor
 * parameter: every existing construction site (the CustomerAddress
 * create / update write Finals, AddressStorageInterface / JSON-backed fake address handler
 * reads, the tests) passes its arguments by name, so the trailing
 * nullable field adds no positional ripple. `null` means the prefecture
 * master was not resolvable (an unset `pref_id`, or the `mtb_pref`
 * master left empty in a structure-only dump).
 *
 * `prefName` is a DISPLAY-only read projection — the WRITE paths
 * (`CustomerAddressCreated` / `CustomerAddressUpdated` →
 * `AddressStorage::put`) persist only `pref` (the FK); they leave
 * `prefName` at its `null` default.
 */
final readonly class AddressEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

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
        public string|null $prefName = null,
    ) {
    }
}
