<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Override;
use Ray\Di\AbstractModule;

/**
 * Production SQL module.
 *
 * All SQL-backed Reasons are Ray.MediaQuery direct proxies registered by
 * {@see MediaQueryRuntimeModule}; no Sql* locator/adaptor binding is needed.
 */
final class SqlModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new MediaQueryRuntimeModule());
    }
}
