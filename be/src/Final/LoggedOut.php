<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Ray\Di\Di\Inject;

/**
 * Logged out — Final, proof the logout request was processed.
 *
 *   LogoutInput → LoggedOut  (this stage — Direct, idempotent)
 *
 * Existence of this object proves: the logout transition has been
 * acknowledged. The public surface reports whether there was actually a
 * logged-in customer at the time of the request (`wasLoggedIn`) and, if
 * so, which one (`customerId`). When there was no session, both fields
 * carry their anonymous values — this is the no-op branch of the
 * idempotent semantics and is NOT an error.
 *
 * Session-clear is deliberately out of scope here. Per the Slice 7.2
 * contract the Be layer is read-only against the HTTP session: the
 * EC-CUBE EventListener observes the LoggedOut response and clears
 * `$_SESSION['customer_id']`. This mirrors Pilot 6's doLogin, where the
 * Be layer returns the proof of authentication and the EventListener
 * writes the session afterwards. Keeping the Be layer session-immutable
 * is the rule established in Slice 6 (CustomerSession is read-only by
 * design — do not mutate it from here).
 */
final readonly class LoggedOut
{
    public bool $wasLoggedIn;

    /** @var non-empty-string|null */
    public string|null $customerId;

    public function __construct(
        #[Inject] CustomerSession $session,
    ) {
        $customerId = $session->customerId;
        $this->customerId = $customerId;
        $this->wasLoggedIn = $customerId !== null;
    }
}
