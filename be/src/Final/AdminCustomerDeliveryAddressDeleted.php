<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\AddressNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAddressAccessException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin customer-delivery address deleted — Final, proof an admin removed
 * one row from an arbitrary customer's address book.
 *
 *   AdminDeleteCustomerDeliveryAddressInput
 *       → AdminCustomerDeliveryAddressDeleted   (Direct, idempotent)
 *
 * AUTHZ check sequencing (admin firewall):
 *   1. No admin session              → 403 (UnauthorizedAdminAccessException)
 *   2. addressId unknown             → 404 (AddressNotFoundException)
 *   3. address owned by someone else → 403 (UnauthorizedAddressAccessException)
 *
 * Step 3 is the EC-CUBE CustomerDeliveryEditController::delete() ownership
 * guard — a tampered addressId pointing at a different customer's row is
 * rejected before deletion.
 */
final readonly class AdminCustomerDeliveryAddressDeleted
{
    public string $addressId;
    public string $customerId;

    public function __construct(
        #[Input] string $customerId,
        #[Input] string $addressId,
        #[Inject] AdminSession $adminSession,
        #[Inject] AddressStorageInterface $addresses,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $addresses->item($addressId);
        if (! $current instanceof AddressEntity) {
            throw new AddressNotFoundException();
        }

        if ($current->customerId !== $customerId) {
            throw new UnauthorizedAddressAccessException();
        }

        $addresses->delete($addressId);

        $this->addressId = $addressId;
        $this->customerId = $customerId;
    }
}
