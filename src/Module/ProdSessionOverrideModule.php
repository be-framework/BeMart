<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\SymfonySessionAdapter;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Override;
use Ray\Di\AbstractModule;

/**
 * Production session binding.
 *
 * AppModule (dev default) binds SessionInterface to
 * FakeSession('customer-001'), which keeps Pilot 1-5 + Slice 6 tests
 * deterministic. In production we must instead derive customerId from
 * the actual HTTP session that EC-CUBE writes to.
 *
 * Slice 7 binds SessionInterface → SymfonySessionAdapter under
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
        $this->bind(SessionInterface::class)->to(SymfonySessionAdapter::class);
    }
}
