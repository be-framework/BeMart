<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\LoggedOut;

/**
 * Input for doLogout — front-end customer logout.
 *
 * Direct pattern (hello-world demo): Input → Final, no intermediate
 * Being. The Final consults SessionInterface to capture who (if anyone)
 * was logged in and emits a proof that the logout request was processed.
 *
 *   LogoutInput → LoggedOut (Final — logout processed)
 *
 * ALPS type=idempotent: re-logging-out an anonymous client is fine —
 * the Final simply records `wasLoggedIn=false` and returns success. The
 * resource layer never maps this to 401.
 *
 * Per the Slice 7.2 contract, the Be layer is read-only against the
 * HTTP session: the EC-CUBE EventListener observes the LoggedOut
 * response and clears `$_SESSION['customer_id']`. This Input therefore
 * takes no body fields — logout is a verb on the current session, not
 * a transformation of submitted data.
 *
 * @link https://schema.org/LogoutAction
 */
#[Be(LoggedOut::class)]
final readonly class LogoutInput
{
    public function __construct()
    {
    }
}
