<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminLoggedOut;

/**
 * Input for doAdminLogout — back-office admin logout.
 *
 * Direct pattern (hello-world demo): Input → Final, no intermediate
 * Being. The Final consults AdminSession to capture which
 * admin (if any) was logged in and emits a proof that the logout
 * request was processed.
 *
 *   AdminLogoutInput → AdminLoggedOut (Final — logout processed)
 *
 * ALPS type=idempotent: re-logging-out an admin-anonymous client is
 * fine — the Final simply records `wasLoggedIn=false` and returns
 * success. The resource layer never maps this to 401/403.
 *
 * Per the Slice 7.2 contract (generalized to the admin firewall in a
 * follow-up wave), the Be layer is read-only against the HTTP session:
 * the EC-CUBE EventListener observes the AdminLoggedOut response and
 * clears the admin session keys. This Input therefore takes no body
 * fields — logout is a verb on the current admin session, not a
 * transformation of submitted data. Same shape as Pilot doLogout for
 * the customer firewall.
 *
 * Source-of-truth gap: the ALPS profile does not currently carry a
 * `doAdminLogout` transition id; using the conventional name to parallel
 * `doLogout` for the customer side.
 *
 * @link https://schema.org/LogoutAction
 */
#[Be(AdminLoggedOut::class)]
final readonly class AdminLogoutInput
{
    public function __construct()
    {
    }
}
