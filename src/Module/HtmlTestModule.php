<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use Override;

/** PHPUnit HTML/Page context: TestModule + HTML presentation. */
final class HtmlTestModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new TestModule($this->appMeta));
        $this->override(new HtmlModule([
            'debug' => true,
            'cache' => false,
            'auto_reload' => true,
        ]));
    }
}
