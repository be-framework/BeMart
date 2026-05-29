<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\SecuritySettingsUpdated;
use MyVendor\BeMart\Be\Input\UpdateSecurityInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSecurityConfigWriter;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class SecuritySettingsUpdatedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;
    private FakeSecurityConfigWriter $securityConfig;

    protected function setUp(): void
    {
        $this->build(self::TEST_ADMIN_ID);
    }

    private function build(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->securityConfig = $injector->getInstance(FakeSecurityConfigWriter::class);
    }

    public function testHappyPathWritesSettings(): void
    {
        $final = ($this->becoming)(new UpdateSecurityInput(
            adminAllowHosts: '192.168.0.0/24',
            trustedHosts: '^example\\.com$',
        ));

        $this->assertInstanceOf(SecuritySettingsUpdated::class, $final);
        $this->assertSame('192.168.0.0/24', $final->adminAllowHosts);
        $this->assertCount(1, $this->securityConfig->writes);
        $this->assertSame('^example\\.com$', $this->securityConfig->settings['trusted_hosts']);
    }

    public function testNoAdminSessionRefuses(): void
    {
        $this->build(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new UpdateSecurityInput(adminAllowHosts: '10.0.0.0/8'));
    }
}
