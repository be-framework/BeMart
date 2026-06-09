<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use Override;

/** PHPUnit context: common app + Ray.FakeQuery + test diagnostics. */
final class TestModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        if ($this->lastModule === null) {
            $this->install(new AppModule($this->appMeta));
        }

        $this->override(new FakeModule($this->appMeta));
        $this->override(new DevModule());
    }
}
