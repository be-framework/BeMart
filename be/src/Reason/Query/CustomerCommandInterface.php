<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;

interface CustomerCommandInterface
{
    public function register(CustomerEntity $customer): void;

    /**
     * Activate a provisional customer — Pilot 7 (doActivateCustomer).
     * Sets customerStatus to 2 (Active) and clears the secretKey. The
     * operation is idempotent: re-activating an already-active customer
     * is a no-op (the ALPS descriptor type for this transition is
     * `idempotent`).
     */
    public function activate(string $customerId): void;

    /**
     * Replace the customer record with the supplied entity — Pilot 8
     * (doUpdateCustomer). Callers MUST construct the entity from the
     * persisted current state merged with the validated update fields;
     * this interface does not perform the merge itself.
     */
    public function update(CustomerEntity $customer): void;
}
