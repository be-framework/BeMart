<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use Override;

use function array_key_exists;

/**
 * Deterministic 2FA boundary for tests. The valid device token is the
 * fixed {@see self::VALID_TOKEN}; any admin with a registered secret
 * accepts it. `test-admin` is seeded with a device so the challenge
 * (`doVerifyTwoFactorAuth`) flow has a configured target out of the box.
 */
final class FakeTwoFactorAuth implements TwoFactorAuthInterface
{
    public const string VALID_TOKEN = '123456';
    public const string FIXED_SECRET = 'JBSWY3DPEHPK3PXP';

    /** @var array<string, string> loginId => secret */
    public array $secrets = ['test-admin' => self::FIXED_SECRET];

    #[Override]
    public function generateSecret(): string
    {
        return self::FIXED_SECRET;
    }

    #[Override]
    public function enable(string $loginId, string $secret): void
    {
        $this->secrets[$loginId] = $secret;
    }

    #[Override]
    public function isEnabled(string $loginId): bool
    {
        return array_key_exists($loginId, $this->secrets);
    }

    #[Override]
    public function verify(string $loginId, string $token): bool
    {
        return array_key_exists($loginId, $this->secrets) && $token === self::VALID_TOKEN;
    }
}
