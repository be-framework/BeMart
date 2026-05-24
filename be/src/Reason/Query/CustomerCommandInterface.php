<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use Ray\MediaQuery\Annotation\DbQuery;

interface CustomerCommandInterface
{
    #[DbQuery('customer_register')]
    public function register(CustomerEntity $customer): void;

    /**
     * Activate a provisional customer — Pilot 7 (doActivateCustomer).
     * Sets customerStatus to 2 (Active) and clears the secretKey. The
     * operation is idempotent: re-activating an already-active customer
     * is a no-op (the ALPS descriptor type for this transition is
     * `idempotent`).
     */
    #[DbQuery('customer_activate')]
    public function activate(string $customerId): void;

    /**
     * Replace the customer record with the supplied entity — Pilot 8
     * (doUpdateCustomer). Callers MUST construct the entity from the
     * persisted current state merged with the validated update fields;
     * this interface does not perform the merge itself.
     */
    #[DbQuery('customer_update')]
    public function update(CustomerEntity $customer): void;

    /**
     * Update the customer's password hash — Pilot 15 (doResetPassword)
     * consumer of Pilot 14's reset token. Caller MUST pass an
     * already-hashed value (computed via PasswordHasherInterface). This
     * interface does NOT accept plaintext: plaintext-handling is the
     * Final's job, persistence is this command's job.
     */
    #[DbQuery('customer_update_password')]
    public function password(string $customerId, string $passwordHash): void;
}
