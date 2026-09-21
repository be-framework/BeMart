<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;

use function headers_sent;
use function session_name;
use function session_start;
use function session_status;

use const PHP_SAPI;
use const PHP_SESSION_ACTIVE;

/**
 * Production {@see SessionStarterInterface}: starts a cookie-backed PHP session under a given
 * cookie name, once, with the options every BeMart session/CSRF adapter has always used.
 *
 * Extracted from three near-identical private methods that used to live on
 * {@see EccubeSharedSessionAdapter}, {@see HtmlAdminSessionAdapter}, and
 * {@see EccubeSharedCsrfTokenAdapter} - each hardcoded the same cookie name and the same options,
 * so which cookie a request's session lived under was a fact only recoverable by reading Auth/
 * adapter internals rather than the context module that wires them. See #93.
 */
final class CookieSessionStarter implements SessionStarterInterface
{
    public function __construct(
        private readonly string $cookieName,
    ) {
    }

    #[Override]
    public function ensureStarted(): void
    {
        // CLI has no real session. Tests can still poke $_SESSION directly;
        // this starter just won't try to start a session machinery that
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
