<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;

use function session_status;

use const PHP_SESSION_ACTIVE;

final class HtmlCustomerSessionWriter implements CustomerSessionWriterInterface
{
    #[Override]
    public function authenticate(string $customerId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] = $customerId;
    }

    #[Override]
    public function clear(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        unset($_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY]);
    }
}
