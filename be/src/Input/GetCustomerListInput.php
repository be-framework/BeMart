<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CustomerListFetched;

/**
 * Input for goCustomerList — admin lists customers with simple filters.
 *
 *   GetCustomerListInput → CustomerListFetched (Final — Direct, safe read)
 *
 * Admin-only endpoint. AUTHN/AUTHZ live in the Final via the Wave 4
 * AdminSessionInterface — a null admin session raises
 * UnauthorizedAdminAccessException, which the BEAR layer maps to 403
 * ("admin login required"). Distinct from the customer-side
 * UnauthenticatedException (Pilot 8 lesson: admin and customer are
 * separate firewalls).
 *
 * Filter scope (Wave 5 first iteration):
 *   - nameKeyword  — substring match across name01 / name02 / companyName
 *   - emailKeyword — substring match on email
 *   Both are optional (null = no filter); when both are supplied they
 *   AND together.
 *
 * TODO(Phase 2): the original EC-CUBE admin search form additionally
 * supports phoneNumber, registration dateRange, and purchase amount
 * filters. Deferred — the first iteration ships the two highest-traffic
 * filters and a TODO marker so the ALPS-driven scope review can ratify
 * the rest.
 *
 * @link https://schema.org/SearchAction
 */
#[Be(CustomerListFetched::class)]
final readonly class GetCustomerListInput
{
    /**
     * Wave 5: every filter field is admin-form input — same taint
     * discipline as the customer-side inputs.
     *
     * @psalm-taint-source input $nameKeyword
     * @psalm-taint-source input $emailKeyword
     * @psalm-taint-source input $limit
     */
    public function __construct(
        public string|null $nameKeyword = null,
        public string|null $emailKeyword = null,
        public int $limit = 50,
    ) {
    }
}
