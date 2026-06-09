<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Override;
use Ray\Di\AbstractModule;

/** Admin test context atom: provide a logged-in fake admin identity. */
final class AdminModule extends AbstractModule
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    #[Override]
    protected function configure(): void
    {
        $this->bind(AdminSession::class)->toInstance(new FakeAdminSession(self::TEST_ADMIN_ID));
    }
}
