<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
use Override;

use function bin2hex;
use function getenv;
use function hash_equals;
use function is_string;
use function random_bytes;

use const PHP_SAPI;

/**
 * Production CsrfTokenInterface adapter — validates submitted tokens
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
 * CLI safety: in `bin/app.php` there is no HTTP origin to defend, but a
 * trusted operator might want to invoke a state-changing endpoint to
 * smoke-test the production binding. The adapter honors a single env
 * var, {@see CLI_ENV_VAR}, as the reference token in CLI context (mirrors
 * Slice 7's {@see EccubeSharedSessionAdapter::CLI_ENV_VAR} pattern). If
 * unset, CLI POSTs fail the CSRF check the same way an anonymous browser
 * request would.
 *
 * Comparison is always timing-safe (`hash_equals`). Empty strings and
 * non-string types are rejected before comparison.
 *
 * Token issuance ({@see getToken()}): the adapter returns the reference
 * already stored under {@see SESSION_KEY}, or — when none is present —
 * generates a cryptographically strong one and stores it back, so a
 * form render and its subsequent POST agree even before the EC-CUBE
 * EventListener mirror ships. It never rotates an existing token.
 */
final class EccubeSharedCsrfTokenAdapter implements CsrfTokenInterface
{
    /**
     * Flat-string session key holding the trusted CSRF reference. EC-CUBE
     * must mirror its active Symfony Forms / form-CSRF token to this key
     * on form render and clear it on logout (parallel to Slice 7's
     * `customer_id` mirror).
     */
    public const SESSION_KEY = '_csrf_token';

    /**
     * CLI-only reference token (operator scripts, subprocess smoke tests).
     * When running under php-cli the adapter compares submitted tokens
     * against this env var if `$_SESSION[SESSION_KEY]` is absent. NEVER
     * set this in HTTP context — it grants any submitter who knows the
     * value the right to bypass CSRF.
     */
    public const CLI_ENV_VAR = 'BEMART_CLI_CSRF_TOKEN';

    public function __construct(
        private readonly string $sessionKey = self::SESSION_KEY,
    ) {
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

        // CLI fallback: operator scripts and subprocess tests may supply
        // the trusted reference via env var. The HTTP path NEVER reaches
        // this branch — `PHP_SAPI` distinguishes web SAPI from cli.
        if (PHP_SAPI === 'cli') {
            $env = getenv(self::CLI_ENV_VAR);
            if ($env !== false && $env !== '' && hash_equals($env, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the session-bound CSRF reference, seeding one if absent.
     *
     * A form render calls this to embed the token in its hidden input;
     * the matching POST echoes it back to {@see isValid()}. The token is
     * generated only once per session — an existing reference is never
     * rotated here, so concurrent form pages in the same session all
     * carry the same valid token.
     */
    #[Override]
    public function getToken(): string
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
}
