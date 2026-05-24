<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use Override;

/** Production HTML/Page context. */
final class HtmlProdModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new ProdModule($this->appMeta));
        $this->override(new HtmlModule());
    }
}
