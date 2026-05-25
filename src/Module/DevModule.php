<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Override;
use Ray\Di\AbstractModule;

/** Development diagnostics modifier. Persistence is composed by the context module. */
final class DevModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->override(new DevLoggingModule());
    }
}
