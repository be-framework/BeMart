<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;
use Ray\Csrf\CsrfTokenInterface;

use function bin2hex;
use function hash_equals;
use function headers_sent;
use function is_string;
use function random_bytes;
use function session_name;
use function session_start;
use function session_status;

use const PHP_SAPI;
use const PHP_SESSION_ACTIVE;

/**
 * EC-CUBE-compatible {@see CsrfTokenInterface} adapter — issues and validates
 * CSRF tokens against the trusted reference stored in PHP's `$_SESSION` under
 * the flat key {@see SESSION_KEY} (shared with EC-CUBE, alongside the flat
 * customerId key Slice 7 already mirrors).
 *
 * This is the BEAR-side half of the bridge. The matching EC-CUBE side — a small
 * EventListener that mirrors the active Symfony Forms / EC-CUBE CSRF token to
 * {@see SESSION_KEY} on form render and rotates it on login/logout — is **not
 * yet implemented**. Until it ships {@see issue()} seeds its own strong token so
 * a form render and its subsequent POST agree, matching Slice 7's
 * split-implementation convention.
 *
 * Wire model (BEAR ↔ EC-CUBE bridge):
 *   1. EC-CUBE writes the active CSRF reference under {@see SESSION_KEY} as a
 *      flat string when a state-changing form is rendered. (Not implemented yet.)
 *   2. BEAR parses the submitted token out of the request and Ray.Csrf's
 *      interceptor asks {@see verify()} to compare it against
 *      `$_SESSION[SESSION_KEY]` with `hash_equals`.
 *
 * CLI safety: there is no HTTP origin to defend in `bin/app.php`; CLI requests
 * use whatever a test/context module seeds into `$_SESSION`, otherwise POSTs
 * fail the CSRF check like an anonymous browser request would.
 *
 * Comparison is always timing-safe (`hash_equals`); empty candidates are
 * rejected. The token never rotates: {@see issue()} returns the stored
 * reference or seeds one.
 */
final readonly class EccubeSharedCsrfTokenAdapter implements CsrfTokenInterface
{
    /**
     * Flat-string session key holding the trusted CSRF reference. EC-CUBE must
     * mirror its active Symfony Forms / form-CSRF token to this key on form
     * render and clear it on logout (parallel to Slice 7's `customer_id` mirror).
     */
    public const SESSION_KEY = '_csrf_token';

    public function __construct(
        private string $sessionKey = self::SESSION_KEY,
    ) {
    }

    /** @return non-empty-string */
    #[Override]
    public function issue(): string
    {
        $this->ensureSessionStarted();

        $stored = $this->storedToken();
        if ($stored !== null) {
            return $stored;
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

        $this->ensureSessionStarted();

        $stored = $this->storedToken();

        return $stored !== null && hash_equals($stored, $candidate);
    }

    #[Override]
    public function clear(): void
    {
        $this->ensureSessionStarted();
        unset($_SESSION[$this->sessionKey]);
    }

    /** @return non-empty-string|null */
    private function storedToken(): string|null
    {
        $session = isset($_SESSION) ? $_SESSION : [];
        /** @var mixed $stored */
        $stored = $session[$this->sessionKey] ?? null;
        if (! is_string($stored) || $stored === '') {
            return null;
        }

        return $stored;
    }

    private function ensureSessionStarted(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (headers_sent()) {
            return;
        }

        session_name(EccubeSharedSessionAdapter::COOKIE_NAME);
        session_start([
            'use_strict_mode' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}
