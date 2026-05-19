<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductDeleted;
use MyVendor\BeMart\Be\Input\AdminDeleteProductInput;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\FakeProductStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminProductDeletedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
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

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $this->injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathSoftDeletesAndReturnsAlreadyDeletedFalse(): void
    {
        $final = ($this->becoming)(new AdminDeleteProductInput('admin-active-001'));

        $this->assertInstanceOf(AdminProductDeleted::class, $final);
        $this->assertSame('admin-active-001', $final->productCode);
        $this->assertFalse($final->alreadyDeleted);

        $storage = $this->injector->getInstance(FakeProductStorage::class);
        $persisted = $storage->getByCode('admin-active-001');
        $this->assertNotNull($persisted);
        $this->assertSame(ProductEntity::STATUS_WITHDRAWN, $persisted->productStatus);
    }

    public function testIdempotentReplayReturnsAlreadyDeletedTrue(): void
    {
        // First delete.
        ($this->becoming)(new AdminDeleteProductInput('admin-active-001'));
        // Second delete (replay).
        $replay = ($this->becoming)(new AdminDeleteProductInput('admin-active-001'));

        $this->assertInstanceOf(AdminProductDeleted::class, $replay);
        $this->assertTrue($replay->alreadyDeleted);
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
