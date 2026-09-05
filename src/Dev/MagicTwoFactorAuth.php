<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Dev;

use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use Override;

/**
 * Development-only 2FA stub that accepts a fixed magic code (123456), so
 * automated / local runs can pass the admin 2FA gate without a real
 * authenticator while the rest of the app still uses real (SQL) data.
 *
 * SECURITY: this MUST never be bound in production. It is wired only by
 * {@see \MyVendor\BeMart\Module\DevloginModule}, which Bootstrap installs
 * only when {@see DevLogin::active()} is true (explicit env opt-in +
 * cli-server SAPI + non-prod context).
 */
final class MagicTwoFactorAuth implements TwoFactorAuthInterface
{
    public const MAGIC = '123456';
    private const SECRET = 'DEVMAGICDEVMAGIC';

    #[Override]
    public function generateSecret(): string
    {
        return self::SECRET;
    }

    #[Override]
    public function generateDeviceToken(string $secret): string
    {
        return self::MAGIC;
    }

    #[Override]
    public function enable(string $loginId, string $secret): void
    {
    }

    #[Override]
    public function isEnabled(string $loginId): bool
    {
        return true;
    }

    #[Override]
    public function verifySecret(string $secret, string $token): bool
    {
        return $token === self::MAGIC;
    }

    #[Override]
    public function verify(string $loginId, string $token): bool
    {
        return $token === self::MAGIC;
    }
}
