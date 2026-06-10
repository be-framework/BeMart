<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;

use function is_string;
use function session_id;
use function session_status;

use const PHP_SESSION_ACTIVE;

final class HtmlCartSessionPrefix implements CartSessionPrefixInterface
{
    public const CART_SESSION_PREFIX_KEY = 'cart_session_prefix';

    #[Override]
    public function prefix(): string|null
    {
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
