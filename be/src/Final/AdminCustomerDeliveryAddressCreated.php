<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use MyVendor\BeMart\Be\Reason\Provider\AddressIdProvider;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin customer-delivery address created — Final, proof an admin added a
 * new row to an arbitrary customer's address book.
 *
 *   AdminCreateCustomerDeliveryAddressInput
 *       → AdminCustomerDeliveryAddressCreated   (Direct, unsafe)
 *
 * AUTHZ — admin firewall (order mirrors {@see AdminShippingAddressUpdated}):
 *   1. No admin session   → 403 (UnauthorizedAdminAccessException) — FIRST,
 *                            so a route-param customerId is never used
 *                            unauthenticated.
 *   2. Unknown customerId → 404 (CustomerNotFoundException)
 *
 * Server-derived fields (mass-assignment shields):
 *   - `addressId` is generated here via AddressIdProvider — the Input never
 *     carries it.
 *   - `customerId` is the route param (admin scope), validated to exist
 *     before the write; the deliberate inversion of the storefront
 *     {@see CustomerAddressCreated} which pulls it from CustomerSession.
 */
final readonly class AdminCustomerDeliveryAddressCreated
{
    public string $addressId;
    public string $customerId;
    public string $name01;
    public string $name02;
    public string|null $kana01;
    public string|null $kana02;
    public string|null $companyName;
    public string|null $phoneNumber;
    public string $postalCode;
    public int $pref;
    public string $addr01;
    public string $addr02;

    public function __construct(
        #[Input] string $customerId,
        #[Input] string $name01,
        #[Input] string $name02,
        #[Input] string $postalCode,
        #[Input] int $pref,
        #[Input] string $addr01,
        #[Input] string $addr02,
        #[Input] string $phoneNumber,
        #[Input] string|null $kana01,
        #[Input] string|null $kana02,
        #[Input] string|null $companyName,
        #[Inject] AdminSession $adminSession,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] AddressStorageInterface $addresses,
        #[Inject] AddressIdProvider $ids,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($customerQuery->item($customerId) === null) {
            throw new CustomerNotFoundException();
        }

        $entity = new AddressEntity(
            addressId: $ids->get(),
            customerId: $customerId,
            name01: $name01,
            name02: $name02,
            kana01: $kana01,
            kana02: $kana02,
            companyName: $companyName,
            phoneNumber: $phoneNumber,
            postalCode: $postalCode,
            pref: $pref,
            addr01: $addr01,
            addr02: $addr02,
        );

        $addresses->put($entity);

        $this->addressId = $entity->addressId;
        $this->customerId = $entity->customerId;
        $this->name01 = $entity->name01;
        $this->name02 = $entity->name02;
        $this->kana01 = $entity->kana01;
        $this->kana02 = $entity->kana02;
        $this->companyName = $entity->companyName;
        $this->phoneNumber = $entity->phoneNumber;
        $this->postalCode = $entity->postalCode;
        $this->pref = $entity->pref;
        $this->addr01 = $entity->addr01;
        $this->addr02 = $entity->addr02;
    }
}
