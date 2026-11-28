<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\Module\ResourceObjectModule;
use BEAR\Package\Module\WriteModule;
use Override;

/** PHPUnit context: common app + Ray.FakeQuery + test diagnostics. */
final class TestModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        if ($this->lastModule === null) {
            // Assembled by hand, not by BEAR\Package\Module: install what its factory adds around the app
            $this->install(new WriteModule($this->appMeta, 'test'));
            $this->install(new AppModule($this->appMeta));
            $this->install(new ResourceObjectModule($this->appMeta->getResourceListGenerator()));
        }

        $this->override(new FakeModule($this->appMeta));
        $this->override(new DevModule());
    }
}
