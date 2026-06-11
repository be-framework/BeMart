<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use Override;

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
 * Production CsrfToken adapter — validates submitted tokens
 * against the trusted reference stored in PHP's `$_SESSION` (alongside
 * the flat customerId key Slice 7 already shares with EC-CUBE).
 *
 * Phase B Slice 8 (CSRF guard, BEAR side only). The matching EC-CUBE
 * side — a small EventListener that mirrors the active Symfony Forms /
 * EC-CUBE CSRF token to {@see SESSION_KEY} on form render and rotates
 * it on login/logout — is **not yet implemented**. Until that ships,
 * every production HTTP POST resolves to "no stored token" → 403. The
 * adapter is the BEAR-side half of the contract, not a complete
 * production CSRF path; this matches Slice 7's split-implementation
 * convention.
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
 * CLI safety: in `bin/app.php` there is no HTTP origin to defend. CLI
 * requests use the token in $_SESSION when a test or context module supplies
 * one; otherwise POSTs fail the CSRF check the same way an anonymous browser
 * request would. Application code must not inspect process environment to
 * decide the trusted token.
 *
 * Comparison is always timing-safe (`hash_equals`). Empty strings and
 * non-string types are rejected before comparison.
 *
 * Token value (`$token`): the adapter snapshots the reference
 * already stored under {@see SESSION_KEY}, or — when none is present —
 * generates a cryptographically strong one and stores it back, so a
 * form render and its subsequent POST agree even before the EC-CUBE
 * EventListener mirror ships. It never rotates an existing token.
 */
final readonly class EccubeSharedCsrfTokenAdapter extends CsrfToken
{
    /**
     * Flat-string session key holding the trusted CSRF reference. EC-CUBE
     * must mirror its active Symfony Forms / form-CSRF token to this key
     * on form render and clear it on logout (parallel to Slice 7's
     * `customer_id` mirror).
     */
    public const SESSION_KEY = '_csrf_token';

    public function __construct(
        private string $sessionKey = self::SESSION_KEY,
    ) {
        $this->ensureSessionStarted();
        parent::__construct($this->resolveToken());
    }

    #[Override]
    public function isValid(string|null $token): bool
    {
        if (! is_string($token) || $token === '') {
            return false;
        }

        $session = isset($_SESSION) ? $_SESSION : [];
        /** @var mixed $stored */
        $stored = $session[$this->sessionKey] ?? null;
        if (is_string($stored) && $stored !== '' && hash_equals($stored, $token)) {
            return true;
        }

        return false;
    }

    /** @return non-empty-string */
    private function resolveToken(): string
    {
        $session = isset($_SESSION) ? $_SESSION : [];
        /** @var mixed $stored */
        $stored = $session[$this->sessionKey] ?? null;
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $token = bin2hex(random_bytes(32));
        if (isset($_SESSION)) {
            $_SESSION[$this->sessionKey] = $token;
        }

        return $token;
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
