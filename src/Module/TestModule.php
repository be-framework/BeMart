<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\Module\WriteModule;
use Override;

/** PHPUnit context: common app + Ray.FakeQuery + test diagnostics. */
final class TestModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        if ($this->lastModule === null) {
            // Assembled by hand, not by BEAR\Package\Module: AppMetaModule resolves the meta through WriteModule's bindings
            $this->install(new WriteModule($this->appMeta, 'test'));
            $this->install(new AppModule($this->appMeta));
        }

        $this->override(new FakeModule($this->appMeta));
        $this->override(new DevModule());
    }
}
