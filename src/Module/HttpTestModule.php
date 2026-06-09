<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\Context\HalModule;
use Override;

/** PHPUnit HTTP context: TestModule + HAL presentation. */
final class HttpTestModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new TestModule($this->appMeta));
        $this->override(new HalModule());
    }
}
