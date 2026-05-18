<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Override;

use function getenv;
use function headers_sent;
use function is_string;
use function session_name;
use function session_start;
use function session_status;

use const PHP_SAPI;
use const PHP_SESSION_ACTIVE;

/**
 * Production SessionInterface adapter — shares EC-CUBE's PHP session.
 *
 * Phase B Slice 7 (Pilot 5 F-1 production binding). Slice 6 introduced
 * SessionInterface with a Fake; this adapter is the real implementation
 * bound under ProdModule.
 *
 * Wire model (BEAR ↔ EC-CUBE bridge):
 *
 *   1. EC-CUBE writes its session under cookie name {@see COOKIE_NAME}.
 *      A small EventListener on the EC-CUBE side mirrors the authenticated
 *      customerId to {@see CUSTOMER_ID_KEY} as a flat string. See
 *      HANDOVER.md "Slice 7 — EC-CUBE 側 contract" for the exact hook.
 *   2. BEAR receives the same cookie (same domain / path). This adapter
 *      starts the session under the same cookie name and reads the flat
 *      key. No Symfony Security deps required on the BEAR side.
 *
 * "Symfony Session 継承" in this slice means *contract* compatibility —
 * BEAR adopts EC-CUBE's cookie name and a documented session key. The
 * adapter never touches Symfony's serialized token storage
 * (`_security_main`) because that would couple us to a specific Symfony
 * Security version. The flat-key approach keeps the bridge stable across
 * Symfony upgrades.
 *
 * CLI safety: in `bin/app.php` we have no HTTP context. `PHP_SAPI === 'cli'`
 * → session machinery is not started. For ad-hoc operator invocations
 * the adapter honors a single env var, {@see CLI_ENV_VAR}, as the
 * authenticated customerId. If unset, CLI requests are anonymous and
 * domain code applies the same AUTHZ rules as for logged-out HTTP. The
 * env var path is intentionally narrow — it bypasses authentication
 * entirely and must never be set in production HTTP context.
 *
 * Headers-sent safety: if `session_start()` cannot run (output already
 * flushed), we silently treat it as anonymous rather than emitting a PHP
 * warning. Domain code already handles `customerId() === null` correctly.
 */
final class SymfonySessionAdapter implements SessionInterface
{
    /**
     * EC-CUBE 4.x cookie name. Override via constructor if a deployment
     * uses a non-default cookie (e.g. multi-tenant subdomain split).
     */
    public const COOKIE_NAME = 'ECCUBE';

    /**
     * Flat-string session key holding the authenticated customerId.
     * EC-CUBE must mirror its authenticated customer to this key on login
     * and clear it on logout. The Symfony Security token under
     * `_security_main` is NOT consulted — this adapter is version-agnostic.
     */
    public const CUSTOMER_ID_KEY = 'customer_id';

    /**
     * CLI-only override: when running under php-cli (e.g. bin/app.php) the
     * adapter consults this env var as the authenticated customerId. Used
     * for operator scripts and the entry-point subprocess tests. NEVER set
     * this in HTTP context — it bypasses authentication entirely.
     */
    public const CLI_ENV_VAR = 'BEMART_CLI_CUSTOMER_ID';

    public function __construct(
        private readonly string $cookieName = self::COOKIE_NAME,
        private readonly string $sessionKey = self::CUSTOMER_ID_KEY,
    ) {
        $this->ensureSessionStarted();
    }

    #[Override]
    public function customerId(): string|null
    {
        // Prefer the live session value when present. Works for HTTP
        // (session_start populates $_SESSION) and for tests that poke
        // $_SESSION directly under PHPUnit.
        $session = isset($_SESSION) ? $_SESSION : [];
        /** @var mixed $raw */
        $raw = $session[$this->sessionKey] ?? null;
        if (is_string($raw) && $raw !== '') {
            return $raw;
        }

        // CLI fallback: operator scripts and subprocess tests may supply
        // an authenticated customerId via env var. Skipped in HTTP context
        // — the adapter never trusts env in that path.
        if (PHP_SAPI === 'cli') {
            $env = getenv(self::CLI_ENV_VAR);
            if ($env !== false && $env !== '') {
                return $env;
            }
        }

        return null;
    }

    private function ensureSessionStarted(): void
    {
        // CLI has no real session. Tests can still poke $_SESSION directly;
        // this adapter just won't try to start a session machinery that
        // would emit a warning or fail.
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (headers_sent()) {
            // Cannot start a session now; treat request as anonymous.
            return;
        }

        session_name($this->cookieName);
        @session_start([
            'use_strict_mode' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}
