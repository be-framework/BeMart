<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use Override;

/**
 * Production application module.
 *
 * Composes AppModule (current dev-default bindings: Be / BEAR / all Reason
 * fakes + DevBecoming logging) and overrides the dev-only logging bindings
 * with prod-safe equivalents from ProdLoggingOverrideModule.
 *
 * The entry point chooses between AppModule (dev) and ProdModule (prod).
 * Pilot 1-5 tests continue to use AppModule unchanged.
 *
 * Future Phase B slices will:
 * - Replace Fake Reasons with real DB-backed implementations under prod
 * - Add CSRF / rate-limit / AUTHZ overrides here
 */
final class ProdModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new AppModule($this->appMeta));
        $this->override(new ProdLoggingOverrideModule());
    }
}
