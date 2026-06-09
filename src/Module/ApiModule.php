<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\Context\ApiModule as PackageApiModule;
use Override;
use Ray\Di\AbstractModule;

/**
 * API context atom.
 *
 * BeMart's canonical API contexts are SQL-backed by default. Fake/test
 * contexts install FakeModule later in the BEAR.Package context chain and
 * override the MediaQuery bindings without changing the public context names.
 */
final class ApiModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new PackageApiModule());
        $this->install(new SqlModule());
    }
}
