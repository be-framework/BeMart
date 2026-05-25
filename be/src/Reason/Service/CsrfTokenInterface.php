<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * CSRF token validator — the boundary check for "did this state-changing
 * request originate from a trusted form on our own origin".
 *
 * Slice 8 (Phase B) introduces this interface. It is consulted by the
 * Resource layer at the start of every state-changing `onPost`. Domain
 * code never sees the token; once validation succeeds the request has
 * proven it came from a trusted form and the Becoming chain proceeds
 * unchanged.
 *
 * Naming note: this lives under Be\Reason\Service but the *validation
 * call* is made by the BEAR Resource layer, not by a Being. CSRF is
 * an HTTP boundary concern — the Be domain has no notion of "request
 * origin". Keeping the interface in Be\Reason makes the production
 * adapter testable through Ray.Di bindings the same way SessionInterface
 * is (Slice 6 pattern), without coupling Be domain to the validation
 * site.
 *
 * Token issuance (form-render / session-store seeding) is intentionally
 * out of scope for this interface. Phase 2 (EC-CUBE migration) will
 * mirror EC-CUBE / Symfony Forms's existing CSRF token into the flat
 * session key this adapter reads, in the same shape as Slice 7.2's
 * planned session mirror.
 */
interface CsrfTokenInterface
{
    /**
     * Returns true iff the supplied token matches the trusted reference
     * for the current request (session-bound). Empty, null, mismatched,
     * or out-of-session tokens always return false. Comparison must be
     * timing-safe (`hash_equals`) — string equality must NEVER be used
     * because production tokens are secrets.
     */
    public function isValid(string|null $token): bool;
}
