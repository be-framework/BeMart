<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use Override;

/** HAL API context backed by Ray.FakeQuery fixtures. */
final class FakeHalApiModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new AppModule($this->appMeta));
        $this->override(new FakeModule());
    }
}
