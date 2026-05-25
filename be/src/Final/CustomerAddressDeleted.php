<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AddressNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAddressAccessException;
use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Customer address deleted — Final, proof one row was removed from
 * the logged-in customer's address book.
 *
 *   DeleteCustomerAddressInput → CustomerAddressDeleted  (Direct)
 *
 * AUTHN + AUTHZ check sequencing — same shape as
 * CustomerAddressUpdated:
 *
 *   1. No session                    → UnauthenticatedException (401)
 *   2. addressId unknown             → AddressNotFoundException  (404)
 *   3. address owned by someone else → UnauthorizedAddressAccessException
 *                                                                (403)
 *
 * 404 on miss (rather than a silent idempotent 200) — Pilot 11
 * doRemoveCartItem pattern: the legitimate AUTHN'd caller deserves
 * to learn the id is bogus. ALPS type=idempotent still holds:
 * re-deleting the same addressId twice both succeed cleanly the
 * first time, then return 404 thereafter; the persisted state
 * (address absent) is reached identically regardless of repetition.
 */
final readonly class CustomerAddressDeleted
{
    public string $addressId;
    public string $customerId;

    public function __construct(
        #[Input] string $addressId,
        #[Inject] CustomerSession $session,
        #[Inject] AddressStorageInterface $addresses,
    ) {
        $sessionCustomerId = $session->customerId;
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

        $addresses->delete($addressId);

        $this->addressId = $addressId;
        $this->customerId = $sessionCustomerId;
    }
}
