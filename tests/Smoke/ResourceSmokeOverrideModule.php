<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Smoke;

use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use Ray\Csrf\CsrfTokenInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Override;
use Ray\Di\AbstractModule;

final class ResourceSmokeOverrideModule extends AbstractModule
{
    public function __construct(
        private readonly bool $admin,
        private readonly string|null $customerId,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind(CsrfTokenInterface::class)->toInstance(new ResourceSmokeCsrfToken());
        if ($this->admin || $this->customerId === null) {
            return;
        }

        $session = new FakeSession($this->customerId);
        $this->bind(FakeSession::class)->toInstance($session);
        $this->bind(CustomerSession::class)->toInstance($session);
    }
}
