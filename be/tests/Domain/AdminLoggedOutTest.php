<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Final\AdminLoggedOut;
use MyVendor\BeMart\Be\Input\AdminLogoutInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminLoggedOutTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private function buildBecoming(string|null $sessionAdminId): BecomingInterface
    {
        $session = new FakeAdminSession($sessionAdminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');

        return $injector->getInstance(BecomingInterface::class);
    }

    public function testLoggedInAdminCapturesAdminId(): void
    {
        $becoming = $this->buildBecoming(self::TEST_ADMIN_ID);

        $final = $becoming(new AdminLogoutInput());

        $this->assertInstanceOf(AdminLoggedOut::class, $final);
        $this->assertTrue($final->wasLoggedIn);
        $this->assertSame(self::TEST_ADMIN_ID, $final->adminId);
    }

    public function testAnonymousIsNotAnError(): void
    {
        // ALPS type=idempotent: the no-session branch is a normal success.
        $becoming = $this->buildBecoming(null);

        $final = $becoming(new AdminLogoutInput());

        $this->assertInstanceOf(AdminLoggedOut::class, $final);
        $this->assertFalse($final->wasLoggedIn);
        $this->assertNull($final->adminId);
    }
}
