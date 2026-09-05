<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use Override;

use function session_regenerate_id;
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

        $this->rotateSessionCredentials();
        $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] = $customerId;
    }

    #[Override]
    public function clear(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        unset($_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY]);
        $this->rotateSessionCredentials();
    }

    /**
     * Retires the id and the CSRF reference the client held under the previous
     * authentication state.
     *
     * Discarding the reference *is* the rotation: {@see EccubeSharedCsrfTokenAdapter}
     * and the Twig widget mint a fresh one on the next read, so a token captured
     * while anonymous stops validating here.
     *
     * Session data survives `session_regenerate_id()`, so the cart prefix
     * {@see HtmlCartSessionPrefix} pinned under the old id still partitions the
     * shopper's cart. Replacing this with `session_destroy()` would orphan it.
     */
    private function rotateSessionCredentials(): void
    {
        session_regenerate_id(true);
        unset($_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
    }
}
