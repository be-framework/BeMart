<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\CookieSessionStarter;
use MyVendor\BeMart\Auth\EccubeSharedSessionAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\SessionStarterInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;
use Ray\Di\AbstractModule;

/**
 * EC-CUBE bridge bindings for production-like contexts.
 *
 * These stay out of {@see ProdModule}: the `prod` token is only the boot-time
 * origin check over BEAR.Package's ProdModule, and the bridge is equally
 * wanted in the non-prod `eccube` contexts.
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
    }
}
