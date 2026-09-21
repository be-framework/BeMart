<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AddressNotFoundException;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAddressAccessException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin customer-delivery address updated — Final, proof an admin edited
 * one row in an arbitrary customer's address book.
 *
 *   AdminUpdateCustomerDeliveryAddressInput
 *       → AdminCustomerDeliveryAddressUpdated   (Direct, idempotent)
 *
 * AUTHZ check sequencing (admin firewall):
 *   1. No admin session              → 403 (UnauthorizedAdminAccessException)
 *   2. Unknown customerId            → 404 (CustomerNotFoundException)
 *   3. addressId unknown             → 404 (AddressNotFoundException)
 *   4. address owned by someone else → 403 (UnauthorizedAddressAccessException)
 *
 * The admin-session shield closes before any route-param id is used.
 * Step 4 is the EC-CUBE CustomerDeliveryEditController ownership guard
 * (CustomerAddress.getCustomer().getId() !== route Customer.getId()): a
 * tampered addressId pointing at a different customer's row is rejected, so
 * the admin cannot move a row across customers.
 *
 * Merge semantics (partial-update convention): every editable field is
 * nullable; null leaves the persisted value untouched.
 */
final readonly class AdminCustomerDeliveryAddressUpdated
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
        #[Inject] AdminSession $adminSession,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] AddressStorageInterface $addresses,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($customerQuery->item($customerId) === null) {
            throw new CustomerNotFoundException();
        }

        $current = $addresses->item($addressId);
        if (! $current instanceof AddressEntity) {
            throw new AddressNotFoundException();
        }

        if ($current->customerId !== $customerId) {
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
