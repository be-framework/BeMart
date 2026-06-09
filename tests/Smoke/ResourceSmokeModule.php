<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Smoke;

use BEAR\AppMeta\Meta as AppMeta;
use MyVendor\BeMart\Module\AdminTestModule;
use MyVendor\BeMart\Module\TestModule;
use Override;
use Ray\Di\AbstractModule;

final class ResourceSmokeModule extends AbstractModule
{
    public function __construct(
        private readonly AppMeta $appMeta,
        private readonly bool $admin,
        private readonly string|null $customerId,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->install($this->admin ? new AdminTestModule($this->appMeta) : new TestModule($this->appMeta));
        $this->override(new ResourceSmokeOverrideModule($this->admin, $this->customerId));
    }
}
