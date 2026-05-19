<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;

/**
 * Customer address book — unified Query + Command (Pilot 16, mirrors
 * the FavoriteStorageInterface convention from Pilot 13). The address
 * workload is trivial enough that one interface handles both reads and
 * writes; a Phase 2 CQRS split into AddressQuery / AddressCommand can
 * happen when the access patterns diverge.
 *
 *   - listByCustomer(customerId): scan the book for one customer
 *   - getById(addressId): look up a single row (ownership AUTHZ at caller)
 *   - put(address): upsert — create on first write, replace on update
 *   - remove(addressId): drop a row (idempotent — silent no-op on miss)
 */
interface AddressStorageInterface
{
    /** @return list<AddressEntity> */
    public function listByCustomer(string $customerId): array;

    public function getById(string $addressId): AddressEntity|null;

    public function put(AddressEntity $address): void;

    public function remove(string $addressId): void;
}
