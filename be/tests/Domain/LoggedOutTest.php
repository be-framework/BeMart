<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Final\LoggedOut;
use MyVendor\BeMart\Be\Input\LogoutInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class LoggedOutTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    private function buildBecoming(string|null $sessionCustomerId): BecomingInterface
    {
        $session = new FakeSession($sessionCustomerId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');

        return $injector->getInstance(BecomingInterface::class);
    }

    public function testLoggedInUserCapturesCustomerId(): void
    {
        $becoming = $this->buildBecoming(self::ALICE_ID);

        $final = $becoming(new LogoutInput());

        $this->assertInstanceOf(LoggedOut::class, $final);
        $this->assertTrue($final->wasLoggedIn);
        $this->assertSame(self::ALICE_ID, $final->customerId);
    }

    public function testAnonymousIsNotAnError(): void
    {
        // ALPS type=idempotent: the no-session branch is a normal success.
        $becoming = $this->buildBecoming(null);

        $final = $becoming(new LogoutInput());

        $this->assertInstanceOf(LoggedOut::class, $final);
        $this->assertFalse($final->wasLoggedIn);
        $this->assertNull($final->customerId);
    }
}
