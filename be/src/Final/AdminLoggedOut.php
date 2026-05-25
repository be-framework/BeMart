<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

/**
 * Admin logged out — Final, proof the admin logout request was
 * processed.
 *
 *   AdminLogoutInput → AdminLoggedOut  (this stage — Direct, idempotent)
 *
 * Existence of this object proves: the admin logout transition has
 * been acknowledged. The public surface reports whether there was
 * actually a logged-in admin at the time of the request
 * (`wasLoggedIn`) and, if so, which one (`adminId`). When there was
 * no admin session, both fields carry their anonymous values — this
 * is the no-op branch of the idempotent semantics and is NOT an
 * error.
 *
 * Session-clear is deliberately out of scope here. Per the Slice 7.2
 * contract (generalized to the admin firewall) the Be layer is
 * read-only against the HTTP session: the EC-CUBE EventListener
 * observes the AdminLoggedOut response and clears the admin session
 * keys. This mirrors Pilot 6 customer doLogin / doLogout, where the
 * Be layer returns the proof and the EventListener writes the
 * session afterwards. Keeping the Be layer session-immutable is the
 * rule established in Slice 6 (sessions are read-only by design — do
 * not mutate them from here).
 */
final readonly class AdminLoggedOut
{
    public bool $wasLoggedIn;

    /** @var non-empty-string|null */
    public string|null $adminId;

    public function __construct(
        #[Inject] AdminSession $session,
    ) {
        $adminId = $session->adminId;
        $this->adminId = $adminId;
        $this->wasLoggedIn = $adminId !== null;
    }
}
