<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Auth\EccubeSharedSessionAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Csrf\CsrfTokenInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;
use Ray\Di\AbstractModule;

/**
 * EC-CUBE bridge bindings for production-like contexts.
 *
 * Keep these app-specific session / CSRF bindings out of ProdModule so the
 * `prod` context token can resolve to BEAR\Package\Context\ProdModule.
 */
final class EccubeModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(CustomerSession::class)->to(EccubeSharedSessionAdapter::class);
        $this->bind(AdminSession::class)->to(HtmlAdminSessionAdapter::class);
        $this->bind(CsrfTokenInterface::class)->to(EccubeSharedCsrfTokenAdapter::class);
    }
}
