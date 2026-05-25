<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AddressIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Customer address created — Final, proof a new shipping address was
 * added to the logged-in customer's book.
 *
 *   CreateCustomerAddressInput → CustomerAddressCreated  (Direct)
 *
 * AUTHN: customerId comes from SessionInterface. A null session
 * raises UnauthenticatedException — the BEAR layer maps this to 401.
 *
 * Server-derived fields (Pilot 5 F-2 + Pilot 8 lesson):
 *   - `addressId` is generated here via AddressIdGeneratorInterface
 *   - `customerId` is pulled from the session — the body never carries it
 *
 * Both shields close the mass-assignment hole: a malicious body that
 * smuggles `addressId` or `customerId` cannot reach these fields
 * because the Input doesn't declare them.
 */
final readonly class CustomerAddressCreated
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
        #[Inject] SessionInterface $session,
        #[Inject] AddressStorageInterface $addresses,
        #[Inject] AddressIdGeneratorInterface $idGenerator,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $addressId = $idGenerator->generate();

        $entity = new AddressEntity(
            addressId: $addressId,
            customerId: $sessionCustomerId,
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
