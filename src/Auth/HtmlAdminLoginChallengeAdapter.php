<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use function is_string;
use function session_regenerate_id;
use function session_status;

use const PHP_SESSION_ACTIVE;

/**
 * Session-backed admin login challenge state for the pre-auth 2FA ladder.
 *
 * Password verification establishes a pending identity here. The normal admin
 * session is elevated only after the setup/challenge token succeeds, so the
 * 2FA resources never need to trust client-supplied login ids or setup secrets.
 */
final class HtmlAdminLoginChallengeAdapter
{
    public const VERIFY_CHALLENGE_KEY = 'admin_2fa_verify_challenge';
    public const SETUP_CHALLENGE_KEY = 'admin_2fa_setup_challenge';

    public function startVerification(string $adminId, string $loginId): void
    {
        $session = &$this->session();
        unset($session[self::SETUP_CHALLENGE_KEY]);
        $session[self::VERIFY_CHALLENGE_KEY] = [
            'adminId' => $adminId,
            'loginId' => $loginId,
        ];
    }

    public function startSetup(string $adminId, string $loginId, string $authKey): void
    {
        $session = &$this->session();
        unset($session[self::VERIFY_CHALLENGE_KEY]);
        $session[self::SETUP_CHALLENGE_KEY] = [
            'adminId' => $adminId,
            'loginId' => $loginId,
            'authKey' => $authKey,
        ];
    }

    public function verificationChallenge(): AdminTwoFactorChallenge|null
    {
        $session = &$this->session();

        return $this->challengeFrom($session[self::VERIFY_CHALLENGE_KEY] ?? null, requiresAuthKey: false);
    }

    public function setupChallenge(): AdminTwoFactorChallenge|null
    {
        $session = &$this->session();

        return $this->challengeFrom($session[self::SETUP_CHALLENGE_KEY] ?? null, requiresAuthKey: true);
    }

    public function completeVerification(AdminTwoFactorChallenge $challenge): void
    {
        $this->regenerateActiveSessionId();
        $session = &$this->session();
        unset($session[self::VERIFY_CHALLENGE_KEY]);
        $session[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = $challenge->adminId;
    }

    public function completeSetup(AdminTwoFactorChallenge $challenge): void
    {
        $this->regenerateActiveSessionId();
        $session = &$this->session();
        unset($session[self::SETUP_CHALLENGE_KEY]);
        $session[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = $challenge->adminId;
    }

    public function regenerateActiveSessionId(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_regenerate_id(true);
    }

    /** @return array<string, mixed> */
    private function &session(): array
    {
        if (! isset($_SESSION)) {
            $_SESSION = [];
        }

        return $_SESSION;
    }

    private function challengeFrom(mixed $raw, bool $requiresAuthKey): AdminTwoFactorChallenge|null
    {
        if (! is_array($raw)) {
            return null;
        }

        /** @var mixed $adminId */
        $adminId = $raw['adminId'] ?? null;
        /** @var mixed $loginId */
        $loginId = $raw['loginId'] ?? null;
        /** @var mixed $authKey */
        $authKey = $raw['authKey'] ?? null;

        if (! is_string($adminId) || $adminId === '' || ! is_string($loginId) || $loginId === '') {
            return null;
        }

        if ($requiresAuthKey) {
            if (! is_string($authKey) || $authKey === '') {
                return null;
            }

            return new AdminTwoFactorChallenge($adminId, $loginId, $authKey);
        }

        return new AdminTwoFactorChallenge($adminId, $loginId);
    }
}
