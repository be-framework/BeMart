<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\Context\HalModule;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Override;
use Ray\Di\AbstractModule;

/**
 * Legacy direct-module PHPUnit context with a logged-in admin session.
 *
 * The standard `admin-test-hal-app` context uses AdminModule; this module is
 * kept only for tests/Smoke/ResourceSmokeModule until that harness moves to
 * the package Injector context path.
 */
final class AdminTestModule extends AbstractAppModule
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    #[Override]
    protected function configure(): void
    {
        $this->install(new TestModule($this->appMeta));
        $this->override(new HalModule());
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $this->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            #[Override]
            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
    }
}
