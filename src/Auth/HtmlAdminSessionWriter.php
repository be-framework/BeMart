<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use BEAR\Csrf\CsrfTokenInterface;
use Override;

use function session_regenerate_id;
use function session_status;

use const PHP_SESSION_ACTIVE;

final readonly class HtmlAdminSessionWriter implements AdminSessionWriterInterface
{
    public function __construct(private CsrfTokenInterface $csrf)
    {
    }

    #[Override]
    public function clear(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        // The pre-auth challenge keys are credentials too:
        // HtmlAdminLoginChallengeAdapter::completeVerification()/completeSetup()
        // write admin_id back from a challenge without re-checking the password,
        // so leaving one behind lets a logged-out session be re-elevated with
        // the second factor alone.
        unset(
            $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::VERIFY_CHALLENGE_KEY],
            $_SESSION[HtmlAdminLoginChallengeAdapter::SETUP_CHALLENGE_KEY],
        );
        if (! session_regenerate_id(true)) {
            throw new SessionRotationException('Admin logout could not rotate the session id.');
        }

        $this->csrf->clear();
    }
}
