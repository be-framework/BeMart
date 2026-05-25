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
 * - Add rate-limit overrides here
 *
 * Slice 7 added ProdSessionOverrideModule: SessionInterface is bound to
 * EccubeSharedSessionAdapter under prod (vs. FakeSession under dev/test).
 * Slice 8 added ProdCsrfOverrideModule: CsrfTokenInterface is bound to
 * EccubeSharedCsrfTokenAdapter under prod (vs. FakeCsrfToken under dev/test).
 */
final class ProdModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new AppModule($this->appMeta));
        $this->override(new ProdLoggingOverrideModule());
        $this->override(new ProdSessionOverrideModule());
        $this->override(new ProdCsrfOverrideModule());
    }
}
