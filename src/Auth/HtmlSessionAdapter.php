<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Override;

use function is_string;

/**
 * HTML-context customer session adapter.
 *
 * `public/index.php` starts the cookie-backed PHP session for
 * APP_CONTEXT=html before dispatch. This adapter only reads the flat
 * customer id written by the html login/logout resources.
 */
final class HtmlSessionAdapter implements SessionInterface
{
    public const CUSTOMER_ID_KEY = 'customer_id';

    #[Override]
    public function customerId(): string|null
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
