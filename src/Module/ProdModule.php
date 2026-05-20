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
 *
 * Phase 2c added SqlModule: the production cutover. It overrides every
 * storage-interface Reason and every *IdGenerator Fake -> Sql and binds a
 * real PDO (from DATABASE_URL), so the prod context runs the SQL-backed
 * Reasons that the bemart-sql suite proved green. AppModule stays
 * Fake-bound, so the test / app (dev) contexts keep their fast, DB-free
 * Fake Reasons.
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
        $this->override(new SqlModule());
    }
}
