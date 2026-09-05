<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Dev;

use function str_contains;

/**
 * Guard for the development 2FA bypass ({@see MagicTwoFactorAuth}).
 *
 * The bypass is active ONLY when ALL of these hold:
 *   - the explicit env opt-in BEMART_DEV_LOGIN=1, AND
 *   - the PHP built-in dev server (`php -S` → SAPI "cli-server"), AND
 *   - a non-prod context.
 *
 * Production (php-fpm, or any `prod-*` context) therefore can never enable
 * it, even if the env var leaks.
 */
final class DevLogin
{
    public const ENV = 'BEMART_DEV_LOGIN';

    /** @param string|false $env value of getenv(self::ENV) */
    public static function active(string|false $env, string $sapi, string $context): bool
    {
        return $env === '1'
            && $sapi === 'cli-server'
            && ! str_contains($context, 'prod');
    }
}
