<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductDeleted;
use MyVendor\BeMart\Be\Input\AdminDeleteProductInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminProductDeletedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathSoftDeletesAndReturnsAlreadyDeletedFalse(): void
    {
        $final = ($this->becoming)(new AdminDeleteProductInput('admin-active-001'));

        $this->assertInstanceOf(AdminProductDeleted::class, $final);
        $this->assertSame('admin-active-001', $final->productCode);
        $this->assertFalse($final->alreadyDeleted);
        // FakeQuery fixtures are static; soft-delete persistence is covered by the SQL suite.
    }

    public function testIdempotentReplayReturnsAlreadyDeletedTrue(): void
    {
        $this->markTestSkipped('Idempotent replay needs mutable persistence; covered by the SQL suite.');
    }

    public function testWithdrawnSeedShortCircuitsToAlreadyDeleted(): void
    {
        $final = ($this->becoming)(new AdminDeleteProductInput('admin-withdrawn-001'));

        $this->assertInstanceOf(AdminProductDeleted::class, $final);
        $this->assertTrue($final->alreadyDeleted);
    }

    public function testUnknownProductRaisesNotFound(): void
    {
        $this->expectException(ProductNotFoundException::class);
        ($this->becoming)(new AdminDeleteProductInput('does-not-exist'));
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminDeleteProductInput('admin-active-001'));
    }
}
