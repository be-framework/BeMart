<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use MyVendor\BeMart\Be\Reason\Service\AdminSession;

use function is_string;

/**
 * HTML-context admin session adapter.
 *
 * `public/index.php` starts the cookie-backed PHP session for the HTML context
 * before dispatch. This adapter snapshots the flat admin id written by the html
 * admin login/logout resources.
 *
 * Which cookie starts that session is a context/DI decision - see
 * {@see SessionStarterInterface} and #93.
 */
final class HtmlAdminSessionAdapter extends AdminSession
{
    public const ADMIN_ID_KEY = 'admin_id';

    public function __construct(
        private readonly SessionStarterInterface $sessionStarter = new CookieSessionStarter(EccubeSharedSessionAdapter::COOKIE_NAME),
    ) {
        parent::__construct($this->readAdminId());
    }

    public function refresh(): void
    {
        $this->adminId = $this->readAdminId();
    }

    /** @return non-empty-string|null */
    private function readAdminId(): string|null
    {
        $this->sessionStarter->ensureStarted();
        $session = isset($_SESSION) ? $_SESSION : [];
        /** @var mixed $raw */
        $raw = $session[self::ADMIN_ID_KEY] ?? null;
        if (is_string($raw) && $raw !== '') {
            return $raw;
        }

        return null;
    }
}
