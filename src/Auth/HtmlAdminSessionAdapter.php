<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Override;

use function is_string;

/**
 * HTML-context admin session adapter.
 *
 * `public/index.php` starts the cookie-backed PHP session for
 * APP_CONTEXT=html before dispatch. This adapter only reads the flat
 * admin id written by the html admin login/logout resources.
 */
final class HtmlAdminSessionAdapter implements AdminSessionInterface
{
    public const ADMIN_ID_KEY = 'admin_id';

    #[Override]
    public function adminId(): string|null
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
