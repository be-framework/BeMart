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
 */
final readonly class HtmlAdminSessionAdapter extends AdminSession
{
    public const ADMIN_ID_KEY = 'admin_id';

    public function __construct()
    {
        parent::__construct(self::readAdminId());
    }

    /** @return non-empty-string|null */
    private static function readAdminId(): string|null
    {
        $session = isset($_SESSION) ? $_SESSION : [];
        /** @var mixed $raw */
        $raw = $session[self::ADMIN_ID_KEY] ?? null;
        if (is_string($raw) && $raw !== '') {
            return $raw;
        }

        return null;
    }
}
