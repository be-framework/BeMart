<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use Override;

/**
 * Production application module.
 *
 * AppModule provides only common BEAR/Be infrastructure. ProdModule then
 * adds production session / CSRF adapters and SQL-backed Ray.MediaQuery
 * proxies through SqlModule. FakeModule is intentionally not installed here.
 */
final class ProdModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        if ($this->lastModule === null) {
            $this->install(new AppModule($this->appMeta));
        }

        $this->override(new ProdSessionOverrideModule());
        $this->override(new ProdCsrfOverrideModule());
        $this->override(new SqlModule());
    }
}
