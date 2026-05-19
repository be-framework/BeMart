<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;

/**
 * Read-side Customer query — Pilot 6 (doLogin).
 *
 * Split from CustomerCommandInterface to keep CQRS boundaries explicit
 * (existing OrderQuery / OrderCommand follow the same convention).
 * The login flow needs to lookup-by-email; new transitions that need
 * "find by id" or "find by reset key" can extend this same interface.
 */
interface CustomerQueryInterface
{
    /** @return CustomerEntity|null  null when no customer has this email. */
    public function findByEmail(string $email): CustomerEntity|null;
}
