<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Be\Reason\Service\TwoFactorAuthInterface;
use MyVendor\BeMart\Dev\MagicTwoFactorAuth;
use Override;
use Ray\Di\AbstractModule;

/**
 * Development-only override that swaps the 2FA service for
 * {@see MagicTwoFactorAuth} (accepts the fixed code 123456) while leaving
 * every other (SQL) binding intact.
 *
 * SECURITY: this is applied as a Bootstrap override only when
 * {@see \MyVendor\BeMart\Dev\DevLogin::active()} is true — never in prod.
 */
final class DevloginModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(TwoFactorAuthInterface::class)->to(MagicTwoFactorAuth::class);
    }
}
