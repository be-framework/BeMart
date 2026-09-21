<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;
use Ray\Csrf\CsrfTokenInterface;

use function bin2hex;
use function hash_equals;
use function is_string;
use function random_bytes;

/**
 * Production Ray\Csrf\CsrfTokenInterface adapter — validates submitted tokens
 * against the trusted reference stored in PHP's `$_SESSION` (alongside
 * the flat customerId key Slice 7 already shares with EC-CUBE).
 *
 * Ray.Csrf's own SessionCsrfToken stores its reference under a private,
 * unconfigurable `ray_csrf_token` session key. BeMart binds
 * Ray\Csrf\CsrfTokenInterface to this adapter instead, so the stored key stays
 * {@see SESSION_KEY} (`_csrf_token`) — the flat string EC-CUBE's Symfony Forms
 * CSRF token is meant to mirror on form render, matching Slice 7's
 * split-implementation convention. Until that EC-CUBE-side mirror ships,
 * every production HTTP POST resolves to "no stored token" → rejected.
 *
 * Wire model (BEAR ↔ EC-CUBE bridge):
 *
 *   1. EC-CUBE writes the active CSRF reference under {@see SESSION_KEY}
 *      as a flat string when a state-changing form is rendered. (See
 *      HANDOVER.md "Slice 8 — EC-CUBE 側 contract" for the exact hook.
 *      Not implemented yet.)
 *   2. BEAR receives the form submission, parses `csrfToken` out of the
 *      JSON / form body, and asks this adapter to compare it against
 *      `$_SESSION[SESSION_KEY]` using `hash_equals`.
 *
 * Which cookie starts the session backing $_SESSION, and whether one starts
 * at all, is a context/DI decision - see {@see SessionStarterInterface} and #93.
 *
 * Comparison is always timing-safe (`hash_equals`). Empty strings and
 * non-string types are rejected before comparison.
 *
 * `issue()`: returns the reference already stored under {@see SESSION_KEY},
 * or — when none is present — generates a cryptographically strong one and
 * stores it back, so a form render and its subsequent POST agree even before
 * the EC-CUBE EventListener mirror ships. It never rotates a reference it
 * finds: rotation is driven by the session lifecycle, where the
 * customer/admin session writers and {@see HtmlAdminLoginChallengeAdapter}
 * discard the reference on every authentication state change and let the
 * next `issue()` mint a fresh one.
 */
final readonly class EccubeSharedCsrfTokenAdapter implements CsrfTokenInterface
{
    /**
     * Flat-string session key holding the trusted CSRF reference. EC-CUBE
     * must mirror its active Symfony Forms / form-CSRF token to this key
     * on form render and clear it on logout (parallel to Slice 7's
     * `customer_id` mirror).
     */
    public const SESSION_KEY = '_csrf_token';

    public function __construct(
        private SessionStarterInterface $sessionStarter = new CookieSessionStarter(EccubeSharedSessionAdapter::COOKIE_NAME),
        private string $sessionKey = self::SESSION_KEY,
    ) {
    }

    #[Override]
    public function issue(): string
    {
        $this->sessionStarter->ensureStarted();

        $existing = $this->storedToken();
        if ($existing !== null) {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        if (isset($_SESSION)) {
            $_SESSION[$this->sessionKey] = $token;
        }

        return $token;
    }

    #[Override]
    public function verify(string $candidate): bool
    {
        if ($candidate === '') {
            return false;
        }

        $this->sessionStarter->ensureStarted();
        $stored = $this->storedToken();

        return $stored !== null && hash_equals($stored, $candidate);
    }

    #[Override]
    public function clear(): void
    {
        $this->sessionStarter->ensureStarted();
        unset($_SESSION[$this->sessionKey]);
    }

    /** @return non-empty-string|null */
    private function storedToken(): string|null
    {
        $session = isset($_SESSION) ? $_SESSION : [];
        /** @var mixed $stored */
        $stored = $session[$this->sessionKey] ?? null;

        return is_string($stored) && $stored !== '' ? $stored : null;
    }
}
