<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\Context\HalModule;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;

/** HTTP workflow context: SQL persistence, HAL rendering, PHP-session auth. */
final class HttpProdHalTestModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new ProdModule($this->appMeta));
        $this->override(new HalModule());
        $this->bind(CustomerSession::class)->to(HtmlSessionAdapter::class);
        $this->bind(AdminSession::class)->to(HtmlAdminSessionAdapter::class);
    }
}
