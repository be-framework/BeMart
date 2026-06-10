<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use MyVendor\BeMart\Be\Reason\Service\CustomerSession;

use function headers_sent;
use function is_string;
use function session_name;
use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

/**
 * Production CustomerSession adapter — reads PHP's `$_SESSION` using
 * EC-CUBE's cookie name and a documented flat customer-id key.
 *
 * Phase B Slice 7 (Pilot 5 F-1 production binding, BEAR side only). The
 * BEAR side of the bridge is implemented here. The matching EC-CUBE side
 * — a small Symfony EventListener that mirrors the authenticated customer
 * id to the flat session key on login and unsets it on logout — is **not
 * yet implemented**. Until that ships, every HTTP request under the
 * EC-CUBE production context resolves to anonymous → AUTHZ rejects everything. The
 * adapter is therefore the BEAR-side half of the contract, not a
 * complete production auth path.
 *
 * The class name deliberately does NOT mention Symfony: this adapter
 * never imports a Symfony class and never touches Symfony Security's
 * serialized token (`_security_main`). It only reads a documented flat
 * key out of PHP's standard `$_SESSION` superglobal. The "Symfony Session
 * 継承" decision (HANDOVER Slice 7) describes the cookie/storage we
 * *share with* EC-CUBE, not a dependency we adopt.
 *
 * Wire model (BEAR ↔ EC-CUBE bridge):
 *
 *   1. EC-CUBE writes its session under cookie name {@see COOKIE_NAME}.
 *      A small EventListener on the EC-CUBE side mirrors the
 *      authenticated customerId to {@see CUSTOMER_ID_KEY} as a flat
 *      string. (See HANDOVER.md "Slice 7 — EC-CUBE 側 contract" for the
 *      exact hook. Not implemented yet.)
 *   2. BEAR receives the same cookie (same domain / path). This adapter
 *      starts the session under the same cookie name and reads the flat
 *      key. No Symfony deps required on the BEAR side.
 *
     * CLI safety: in `bin/app.php` we have no HTTP context. CLI requests are
     * anonymous unless a context module binds a different CustomerSession.
     * Application code must not inspect process environment to decide auth.
 *
 * Headers-sent safety: if `session_start()` cannot run (output already
 * flushed), we treat the request as anonymous. Domain code already
 * handles `customerId === null` correctly.
 */
final readonly class EccubeSharedSessionAdapter extends CustomerSession
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

    public function __construct(
        private string $cookieName = self::COOKIE_NAME,
        private string $sessionKey = self::CUSTOMER_ID_KEY,
    ) {
        $this->ensureSessionStarted();
        parent::__construct($this->readCustomerId());
    }

    /** @return non-empty-string|null */
    private function readCustomerId(): string|null
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
        // No error suppression: if session_start emits a warning, surface
        // it. The headers_sent() guard above covers the common case;
        // other failures (session.save_path unwritable, etc.) are
        // operator-config issues that must be visible in the error log,
        // not silently swallowed into "request is anonymous".
        session_start([
            'use_strict_mode' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}
