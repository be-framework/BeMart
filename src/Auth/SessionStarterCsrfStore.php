<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use BEAR\Csrf\CsrfSessionKey;
use BEAR\Csrf\CsrfStoreInterface;
use Override;

use function is_string;

/**
 * BEAR.Csrf token storage that opens the session through {@see SessionStarterInterface}.
 *
 * The bundled `SessionCsrfStore` calls `session_start()` itself, which bypasses the
 * EC-CUBE cookie name and start policy the context module chose. Token semantics
 * (issue / verify / clear) stay in BEAR.Csrf's `StoredCsrfToken`; only the slot is ours.
 */
final readonly class SessionStarterCsrfStore implements CsrfStoreInterface
{
    public function __construct(
        private SessionStarterInterface $sessionStarter,
        private CsrfSessionKey $sessionKey = new CsrfSessionKey(),
    ) {
    }

    #[Override]
    public function get(): string|null
    {
        $this->sessionStarter->ensureStarted();
        $stored = $_SESSION[$this->sessionKey->name] ?? null;

        return is_string($stored) && $stored !== '' ? $stored : null;
    }

    #[Override]
    public function set(string $token): void
    {
        $this->sessionStarter->ensureStarted();
        $_SESSION[$this->sessionKey->name] = $token;
    }

    #[Override]
    public function remove(): void
    {
        $this->sessionStarter->ensureStarted();
        unset($_SESSION[$this->sessionKey->name]);
    }
}
