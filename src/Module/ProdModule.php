<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\Context\ProdModule as PackageProdModule;
use Override;
use Ray\Di\AbstractModule;

/**
 * Production context: BEAR.Package's ProdModule, plus the real same-origin origin.
 *
 * {@see AppModule} always installs `CsrfModule::withoutSameOriginCheck()`, leaving
 * the same-origin gate off (`AllowedOrigin` null) so it is context-neutral. An
 * absent origin in production would leave that gate down for every request,
 * silently, so this module reads the env once at compile time, refuses to boot
 * without it, and rebinds `AllowedOrigin` to turn the gate on.
 */
final class ProdModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $origin = AllowedOriginEnv::value();
        if ($origin === null) {
            throw new MissingAllowedOriginException(AllowedOriginEnv::NAME);
        }

        $this->install(new PackageProdModule());
        $this->install(new AllowedOriginModule($origin));
    }
}
