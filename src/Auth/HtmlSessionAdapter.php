<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use MyVendor\BeMart\Be\Reason\Service\CustomerSession;

use function is_string;

/**
 * HTML-context customer session adapter.
 *
 * The cookie-backed PHP session is started via {@see SessionStarterInterface} (a context/DI
 * decision, not this class's - see #93) before this adapter ever reads it. This adapter itself
 * only snapshots the flat customer id written by the html login/logout resources.
 */
final readonly class HtmlSessionAdapter extends CustomerSession
{
    public const CUSTOMER_ID_KEY = 'customer_id';

    public function __construct(
        private SessionStarterInterface $sessionStarter = new CookieSessionStarter(EccubeSharedSessionAdapter::COOKIE_NAME),
    ) {
        parent::__construct($this->readCustomerId());
    }

    /** @return non-empty-string|null */
    private function readCustomerId(): string|null
    {
        $this->sessionStarter->ensureStarted();
        $session = isset($_SESSION) ? $_SESSION : [];
        /** @var mixed $raw */
        $raw = $session[self::CUSTOMER_ID_KEY] ?? null;
        if (is_string($raw) && $raw !== '') {
            return $raw;
        }

        return null;
    }
}
