<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * AdminSession — the AAA boundary for "which admin is making this
 * request" (parallel firewall to {@see SessionInterface} for customers).
 *
 * Returns the authenticated adminId for the current request, or null
 * for anonymous (unauthenticated-as-admin) requests. Domain code
 * consults this for admin-side AUTHZ — proving "the request comes from
 * an admin firewall" before exposing admin-only data.
 *
 * Wave 4 introduces this interface alongside {@see SessionInterface}.
 * The two are intentionally NOT unified: EC-CUBE itself uses Symfony's
 * `security.firewalls` with two separate firewalls (`admin` + `customer`),
 * and admins / customers are distinct AAA principal classes. A logged-in
 * customer is NOT logged-in-as-admin, and vice versa. Wave 5 will use
 * this interface to refuse customer-side requests at admin endpoints
 * via {@see \MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException}.
 *
 * Production adapter (cookie / JWT / server-side store) is out of scope
 * for Wave 4; deferred to a later slice with explicit user judgment on
 * session storage, same convention as customer-side SessionInterface.
 */
interface AdminSessionInterface
{
    /**
     * Wave 4: the returned adminId originates from the HTTP session,
     * which in turn is set by an upstream admin-login flow that the
     * BEAR layer does not control (production: EC-CUBE-side
     * EventListener mirror, same shape as customer Slice 7.2 contract).
     * Treat the value as user-controlled-but-bounded: a logged-in admin
     * cannot directly choose their id, but the session store is part of
     * the AAA trust boundary. Marked as a `session` taint source so
     * flows that reach sensitive sinks (DB / mailer / gateway) surface
     * explicitly — same discipline as customer SessionInterface.
     *
     * @return non-empty-string|null  adminId, or null if no admin is logged in
     *
     * @psalm-taint-source session
     */
    public function adminId(): string|null;
}
