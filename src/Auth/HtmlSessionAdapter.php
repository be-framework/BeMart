<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use MyVendor\BeMart\Be\Reason\Service\CustomerSession;

use function is_string;

/**
 * HTML-context customer session adapter.
 *
 * `public/index.php` starts the cookie-backed PHP session for the HTML context
 * before dispatch. This adapter snapshots the flat customer id written by the
 * html login/logout resources.
 */
final readonly class HtmlSessionAdapter extends CustomerSession
{
    public const CUSTOMER_ID_KEY = 'customer_id';

    public function __construct()
    {
        parent::__construct(self::readCustomerId());
    }

    /** @return non-empty-string|null */
    private static function readCustomerId(): string|null
    {
        $session = isset($_SESSION) ? $_SESSION : [];
        /** @var mixed $raw */
        $raw = $session[self::CUSTOMER_ID_KEY] ?? null;
        if (is_string($raw) && $raw !== '') {
            return $raw;
        }

        return null;
    }
}
