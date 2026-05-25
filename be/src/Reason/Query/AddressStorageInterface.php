<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Customer address book — unified Query + Command (Pilot 16, mirrors
 * the FavoriteStorageInterface convention from Pilot 13). The address
 * workload is trivial enough that one interface handles both reads and
 * writes; a Phase 2 CQRS split into AddressQuery / AddressCommand can
 * happen when the access patterns diverge.
 *
 *   - listByCustomer(customerId): scan the book for one customer
 *   - item(addressId): look up a single row (ownership AUTHZ at caller)
 *   - put(address): upsert — create on first write, replace on update
 *   - delete(addressId): drop a row (idempotent — silent no-op on miss)
 */
interface AddressStorageInterface
{
    /** @return list<AddressEntity> */
    #[DbQuery('address_list_by_customer')]
    public function listByCustomer(string $customerId): array;

    #[DbQuery('address_get')]
    public function item(string $addressId): AddressEntity|null;

    #[DbQuery('address_put')]
    public function put(AddressEntity $address): void;

    #[DbQuery('address_delete')]
    public function delete(string $addressId): void;
}
