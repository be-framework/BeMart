<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use function getenv;
use function is_string;
use function session_id;
use function session_status;
use function str_contains;

use const PHP_SESSION_ACTIVE;

/**
 * HTML-context cart session anchor.
 *
 * `public/index.php` starts PHP's cookie-backed session for an html APP_CONTEXT.
 * The storefront cart key prefix is derived once from that PHP session id and
 * then kept in $_SESSION so every resource request in the browser session uses
 * the same `{sessionPrefix}_{saleTypeId}` cart partition.
 */
final class HtmlCartSession
{
    public const CART_SESSION_PREFIX_KEY = 'cart_session_prefix';

    public static function cartSessionPrefix(): string|null
    {
        if (! str_contains((string) getenv('APP_CONTEXT'), 'html')) {
            return null;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        /** @var mixed $raw */
        $raw = $_SESSION[self::CART_SESSION_PREFIX_KEY] ?? null;
        if (is_string($raw) && $raw !== '') {
            return $raw;
        }

        $sessionId = session_id();
        if (! is_string($sessionId) || $sessionId === '') {
            return null;
        }

        $_SESSION[self::CART_SESSION_PREFIX_KEY] = $sessionId;

        return $sessionId;
    }
}
