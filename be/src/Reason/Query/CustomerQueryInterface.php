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

    /**
     * Look up a customer by email-verification secret key — Pilot 7
     * (doActivateCustomer). Returns null on miss; callers MUST NOT
     * distinguish "wrong key" from "expired" at this layer.
     */
    public function findBySecretKey(string $secretKey): CustomerEntity|null;

    /**
     * Look up a customer by their opaque id — Pilot 8 (doUpdateCustomer
     * and other "the logged-in customer is editing themselves" flows).
     */
    public function findById(string $customerId): CustomerEntity|null;

    /**
     * Pilot Wave 5 goCustomerList: admin-side filter search over the
     * customer corpus. Both keywords are substring-matched and ANDed
     * when both are present; pass null to disable that filter. Returns
     * an empty list when no customer matches.
     *
     * Filter scope (first iteration): name (matched against name01,
     * name02, companyName) and email. Phase 2 will add phoneNumber,
     * dateRange, and purchaseAmount filters per the original EC-CUBE
     * admin search form. Caller-supplied `$limit` caps the result set
     * size (default 50) so the admin grid stays bounded even for a
     * "blank" search.
     *
     * @return list<CustomerEntity>
     */
    public function search(?string $nameKeyword, ?string $emailKeyword, int $limit = 50): array;
}
