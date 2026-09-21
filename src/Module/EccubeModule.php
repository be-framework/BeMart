<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\CookieSessionStarter;
use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Auth\EccubeSharedSessionAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\SessionStarterInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;
use BEAR\Csrf\CsrfTokenInterface;
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
        // Explicit here so the cookie every EC-CUBE-bridge session/CSRF adapter shares is a
        // context/DI fact, not something read out of Auth/ adapter internals. See #93.
        $this->bind(SessionStarterInterface::class)->toInstance(new CookieSessionStarter(EccubeSharedSessionAdapter::COOKIE_NAME));
        $this->bind(CustomerSession::class)->to(EccubeSharedSessionAdapter::class);
        $this->bind(AdminSession::class)->to(HtmlAdminSessionAdapter::class);
        $this->bind(CsrfTokenInterface::class)->to(EccubeSharedCsrfTokenAdapter::class);
    }
}
