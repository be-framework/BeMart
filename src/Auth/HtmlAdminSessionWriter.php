<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;

use function session_status;

use const PHP_SESSION_ACTIVE;

final class HtmlAdminSessionWriter implements AdminSessionWriterInterface
{
    #[Override]
    public function clear(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        unset($_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY]);
    }
}
