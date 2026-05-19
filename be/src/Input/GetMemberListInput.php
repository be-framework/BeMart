<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\MemberListFetched;

/**
 * Input for goMemberList — admin lists admin members (Wave 8).
 *
 *   GetMemberListInput → MemberListFetched (Final — Direct, safe read)
 *
 * Admin-only endpoint. AUTHN/AUTHZ live in the Final via the Wave 4
 * AdminSessionInterface — a null admin session raises
 * UnauthorizedAdminAccessException, which the BEAR layer maps to 403.
 *
 * Filter scope (Wave 8 first iteration):
 *   - nameKeyword  — substring match on admin's display `name`
 *   - limit        — caps the result set (default 50)
 *   - offset       — pagination cursor (default 0)
 *
 * Distinct from {@see GetCustomerListInput}: admins are a different
 * AAA principal class, so the list endpoints are namespaced
 * separately (Page\Admin\MemberList vs Page\Admin\CustomerList).
 */
#[Be(MemberListFetched::class)]
final readonly class GetMemberListInput
{
    /**
     * Wave 8: filter fields are admin-form input — same taint
     * discipline as the customer-list variant.
     *
     * @psalm-taint-source input $nameKeyword
     * @psalm-taint-source input $limit
     * @psalm-taint-source input $offset
     */
    public function __construct(
        public string|null $nameKeyword = null,
        public int $limit = 50,
        public int $offset = 0,
    ) {
    }
}
