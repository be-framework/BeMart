<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use Override;

/** HTML/Page context backed by SQL MediaQuery. */
final class HtmlHalModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new HalApiModule($this->appMeta));
        $this->override(new HtmlModule());
    }
}
