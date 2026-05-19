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
}
