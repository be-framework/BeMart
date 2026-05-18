<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
use Override;
use Ray\Di\AbstractModule;

/**
 * Production CSRF binding.
 *
 * AppModule (dev default) binds CsrfTokenInterface to FakeCsrfToken,
 * which validates against a known test reference so Pilot 2-5 resource
 * tests stay deterministic. In production we must instead validate
 * against the trusted reference EC-CUBE writes to the shared PHP
 * session.
 *
 * Slice 8 binds CsrfTokenInterface → EccubeSharedCsrfTokenAdapter under
 * ProdModule, parallel to Slice 7's ProdSessionOverrideModule. The
 * adapter is request-scoped (no Singleton) — each Injector resolution
 * inspects the live `$_SESSION` and CLI env independently.
 *
 * The bridge contract (session key + CLI env var) is documented on the
 * adapter class.
 */
final class ProdCsrfOverrideModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(CsrfTokenInterface::class)->to(EccubeSharedCsrfTokenAdapter::class);
    }
}
