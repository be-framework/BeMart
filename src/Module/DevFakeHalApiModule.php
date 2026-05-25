<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use Override;

/** Fake HAL API context with development diagnostics enabled. */
final class DevFakeHalApiModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new FakeModule($this->appMeta));
        $this->override(new DevModule());
    }
}
