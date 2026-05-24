<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AddressNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAddressAccessException;
use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Customer address updated — Final, proof one row in the logged-in
 * customer's address book was edited in place.
 *
 *   UpdateCustomerAddressInput → CustomerAddressUpdated  (Direct)
 *
 * AUTHN + AUTHZ check sequencing (Pilot 12 lesson, mirroring
 * MypageHistoryFetched):
 *
 *   1. No session                    → UnauthenticatedException (401)
 *   2. addressId unknown             → AddressNotFoundException  (404)
 *   3. address owned by someone else → UnauthorizedAddressAccessException
 *                                                                (403)
 *
 * Anonymous requests are rejected before existence is probed (no
 * enumeration). Existence precedes AUTHZ so the 404 / 403 distinction
 * survives for a legitimate but-unauthorized caller.
 *
 * Merge semantics (Pilot 8 partial-update convention): every editable
 * field is nullable; null leaves the persisted value untouched.
 */
final readonly class CustomerAddressUpdated
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
        #[Input] string $addressId,
        #[Input] string|null $name01,
        #[Input] string|null $name02,
        #[Input] string|null $kana01,
        #[Input] string|null $kana02,
        #[Input] string|null $companyName,
        #[Input] string|null $postalCode,
        #[Input] int|null $pref,
        #[Input] string|null $addr01,
        #[Input] string|null $addr02,
        #[Input] string|null $phoneNumber,
        #[Inject] SessionInterface $session,
        #[Inject] AddressStorageInterface $addresses,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $current = $addresses->item($addressId);
        if (! $current instanceof AddressEntity) {
            throw new AddressNotFoundException();
        }

        if ($current->customerId !== $sessionCustomerId) {
            throw new UnauthorizedAddressAccessException();
        }

        $merged = new AddressEntity(
            addressId: $current->addressId,
            customerId: $current->customerId,
            name01: $name01 ?? $current->name01,
            name02: $name02 ?? $current->name02,
            kana01: $kana01 ?? $current->kana01,
            kana02: $kana02 ?? $current->kana02,
            companyName: $companyName ?? $current->companyName,
            phoneNumber: $phoneNumber ?? $current->phoneNumber,
            postalCode: $postalCode ?? $current->postalCode,
            pref: $pref ?? $current->pref,
            addr01: $addr01 ?? $current->addr01,
            addr02: $addr02 ?? $current->addr02,
        );

        $addresses->put($merged);

        $this->addressId = $merged->addressId;
        $this->customerId = $merged->customerId;
        $this->name01 = $merged->name01;
        $this->name02 = $merged->name02;
        $this->kana01 = $merged->kana01;
        $this->kana02 = $merged->kana02;
        $this->companyName = $merged->companyName;
        $this->phoneNumber = $merged->phoneNumber;
        $this->postalCode = $merged->postalCode;
        $this->pref = $merged->pref;
        $this->addr01 = $merged->addr01;
        $this->addr02 = $merged->addr02;
    }
}
