<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\EccubeSharedSessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;
use Ray\Di\AbstractModule;

/**
 * Production session binding.
 *
 * Dev/test contexts bind CustomerSession to a deterministic test
 * session through FakeModule. In production we must instead derive
 * customerId from the actual HTTP session that EC-CUBE writes to.
 *
 * Slice 7 binds CustomerSession → EccubeSharedSessionAdapter under
 * ProdModule. The adapter is request-scoped by default in Ray.Di
 * (no Singleton declared) — each Injector resolution starts /
 * inspects the active PHP session.
 *
 * The bridge contract (cookie name + flat customerId key) is
 * documented on the adapter class.
 */
final class ProdSessionOverrideModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(CustomerSession::class)->to(EccubeSharedSessionAdapter::class);
    }
}
