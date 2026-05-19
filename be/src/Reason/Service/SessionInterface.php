<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Session — the AAA boundary for "who is making this request".
 *
 * Returns the authenticated customerId for the current request, or null
 * for anonymous (unauthenticated) requests. Domain code consults this
 * solely for ownership checks (e.g. "the preOrderId this client is
 * confirming actually belongs to them"). Authentication itself —
 * password verification, token issuance, cookie management — is the
 * adapter's concern and not represented here.
 *
 * Phase B Slice 6 introduces this interface specifically for Pilot 5
 * F-1 (AUTHZ): `CheckoutPrepared` compares `$order->customerId` against
 * `$session->customerId()` and refuses to proceed on mismatch.
 *
 * Production adapter (cookie / JWT / server-side store) is intentionally
 * out of scope for Slice 6; that decision is deferred to a later slice
 * with explicit user judgment on session storage.
 */
interface SessionInterface
{
    /**
     * Phase B Slice 9: the returned customerId originates from the HTTP
     * session, which in turn was set by an upstream login flow that the
     * BEAR layer does not control (production: EC-CUBE-side EventListener,
     * Slice 7.2 contract). Treat the value as user-controlled-but-bounded:
     * a logged-in customer cannot directly choose their id, but the
     * session store is part of the AAA trust boundary. Marked as a
     * `session` taint source so flows that reach sensitive sinks
     * (DB / mailer / gateway) surface explicitly.
     *
     * @return non-empty-string|null  customerId, or null if unauthenticated
     *
     * @psalm-taint-source session
     */
    public function customerId(): string|null;
}
