<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use MyVendor\BeMart\Be\Reason\Service\AdminSession;

use function headers_sent;
use function is_string;
use function session_name;
use function session_start;
use function session_status;

use const PHP_SAPI;
use const PHP_SESSION_ACTIVE;

/**
 * HTML-context admin session adapter.
 *
 * `public/index.php` starts the cookie-backed PHP session for the HTML context
 * before dispatch. This adapter snapshots the flat admin id written by the html
 * admin login/logout resources.
 */
final class HtmlAdminSessionAdapter extends AdminSession
{
    public const ADMIN_ID_KEY = 'admin_id';

    public function __construct()
    {
        parent::__construct(self::readAdminId());
    }

    public function refresh(): void
    {
        $this->adminId = self::readAdminId();
    }

    /** @return non-empty-string|null */
    private static function readAdminId(): string|null
    {
        self::ensureSessionStarted();
        $session = isset($_SESSION) ? $_SESSION : [];
        /** @var mixed $raw */
        $raw = $session[self::ADMIN_ID_KEY] ?? null;
        if (is_string($raw) && $raw !== '') {
            return $raw;
        }

        return null;
    }

    private static function ensureSessionStarted(): void
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
