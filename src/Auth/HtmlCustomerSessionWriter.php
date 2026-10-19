<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use BEAR\Csrf\CsrfTokenInterface;
use Override;

use function session_regenerate_id;
use function session_status;

use const PHP_SESSION_ACTIVE;

final readonly class HtmlCustomerSessionWriter implements CustomerSessionWriterInterface
{
    public function __construct(private CsrfTokenInterface $csrf)
    {
    }

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
     * Discarding the reference *is* the rotation: the next `csrf_field()` render
     * mints a fresh one, so a token captured while anonymous stops validating here.
     *
     * Session data survives `session_regenerate_id()`, so the cart prefix
     * {@see HtmlCartSessionPrefix} pinned under the old id still partitions the
     * shopper's cart. Replacing this with `session_destroy()` would orphan it.
     */
    private function rotateSessionCredentials(): void
    {
        if (! session_regenerate_id(true)) {
            throw new SessionRotationException('Customer login/logout could not rotate the session id.');
        }

        $this->csrf->clear();
    }
}
