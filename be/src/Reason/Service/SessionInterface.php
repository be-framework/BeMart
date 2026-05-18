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
     * @return non-empty-string|null  customerId, or null if unauthenticated
     */
    public function customerId(): string|null;
}
