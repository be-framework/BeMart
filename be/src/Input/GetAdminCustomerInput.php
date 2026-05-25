<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminCustomerFetched;

/**
 * Input for goCustomer — admin views a specific customer's detail page.
 *
 * Direct pattern (hello-world demo): Input → Final, no intermediate
 * Being. The Final injects AdminSessionInterface to refuse non-admin
 * requests (Wave 4 cross-firewall AUTHZ) and then aggregates the
 * customer's profile + full order history + favorites.
 *
 *   GetAdminCustomerInput → AdminCustomerFetched (Final — Direct safe read)
 *
 * AUTHZ design:
 *   The adminId is INTENTIONALLY ABSENT from this Input — it lives in
 *   the admin session, not the request body. The request only carries
 *   the *target* email (which customer to inspect). Same mass-assignment
 *   discipline as goMypage / goMypageHistory, just rotated 90°: there
 *   we hide the customerId-of-the-viewer; here we hide the adminId-of-
 *   the-viewer and surface the customer-being-viewed.
 *
 * The descriptor uses `email` for lookup (ALPS profile carries `#email`
 * as the only descriptor), which matches EC-CUBE's admin customer-list
 * URL style — emails are unique among active customers (Pilot 4) so a
 * single record resolves per email.
 *
 * @link https://schema.org/ViewAction
 */
#[Be(AdminCustomerFetched::class)]
final readonly class GetAdminCustomerInput
{
    /**
     * Wave 5: the email is user-controlled input from the admin UI
     * (admin types it / clicks a row in the customer-list). Same
     * taint discipline as the customer-side LoginInput's email.
     *
     * @psalm-taint-source input $email
     */
    public function __construct(
        public string $email,
    ) {
    }
}
