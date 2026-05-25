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
 * Token issuance ({@see getToken()}) seeds the trusted reference so an
 * HTML form can echo it back. The dev {@see FakeCsrfToken} returns a
 * fixed reference; the production {@see EccubeSharedCsrfTokenAdapter}
 * returns the session-bound reference, generating one if absent so a
 * form render and its subsequent POST agree without an external mirror.
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

    /**
     * Returns the trusted CSRF reference token for the current request —
     * the value an HTML form page must render into its hidden `_token` /
     * `_csrf_token` input so the subsequent {@see isValid()} call on the
     * form POST passes.
     *
     * The returned token is stable for the lifetime of the request /
     * session: calling it on form render and validating the same value
     * on the POST is the round-trip this interface guarantees.
     */
    public function getToken(): string;
}
