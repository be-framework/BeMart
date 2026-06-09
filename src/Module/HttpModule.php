<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;
use Ray\Di\AbstractModule;

/** HTTP workflow context atom: read identity from the active PHP session. */
final class HttpModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(CustomerSession::class)->to(HtmlSessionAdapter::class);
        $this->bind(AdminSession::class)->to(HtmlAdminSessionAdapter::class);
    }
}
