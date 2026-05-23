<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductsStatusBulkUpdated;
use MyVendor\BeMart\Be\Input\AdminBulkUpdateProductStatusInput;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeProductStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminProductsStatusBulkUpdatedTest extends TestCase
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

    public function testHappyPathFlipsMultipleProducts(): void
    {
        $final = ($this->becoming)(new AdminBulkUpdateProductStatusInput(
            productCodes: ['admin-active-001', 'admin-hidden-001'],
            productStatus: ProductEntity::STATUS_WITHDRAWN,
        ));

        $this->assertInstanceOf(AdminProductsStatusBulkUpdated::class, $final);
        $this->assertSame(2, $final->requestedCount);
        $this->assertSame(2, $final->changedCount);

        $storage = $this->injector->getInstance(FakeProductStorage::class);
        $this->assertSame(
            ProductEntity::STATUS_WITHDRAWN,
            $storage->getByCode('admin-active-001')?->productStatus,
        );
        $this->assertSame(
            ProductEntity::STATUS_WITHDRAWN,
            $storage->getByCode('admin-hidden-001')?->productStatus,
        );
    }

    public function testUnknownCodesAreSilentlySkipped(): void
    {
        $final = ($this->becoming)(new AdminBulkUpdateProductStatusInput(
            productCodes: ['admin-active-001', 'does-not-exist-001'],
            productStatus: ProductEntity::STATUS_HIDDEN,
        ));

        $this->assertInstanceOf(AdminProductsStatusBulkUpdated::class, $final);
        $this->assertSame(2, $final->requestedCount);
        $this->assertSame(1, $final->changedCount);
    }

    public function testIdempotentReplayDoesNotCount(): void
    {
        // First flip moves both to HIDDEN.
        ($this->becoming)(new AdminBulkUpdateProductStatusInput(
            productCodes: ['admin-active-001', 'admin-hidden-001'],
            productStatus: ProductEntity::STATUS_HIDDEN,
        ));

        // Second call with the same target — already aligned.
        $replay = ($this->becoming)(new AdminBulkUpdateProductStatusInput(
            productCodes: ['admin-active-001', 'admin-hidden-001'],
            productStatus: ProductEntity::STATUS_HIDDEN,
        ));

        $this->assertSame(0, $replay->changedCount);
        $this->assertSame(2, $replay->requestedCount);
    }

    public function testInvalidStatusRaisesSemanticVariableException(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new AdminBulkUpdateProductStatusInput(
            productCodes: ['admin-active-001'],
            productStatus: 99,
        ));
    }

    public function testEmptyListRaisesSemanticVariableException(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new AdminBulkUpdateProductStatusInput(
            productCodes: [],
            productStatus: 1,
        ));
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminBulkUpdateProductStatusInput(
            productCodes: ['admin-active-001'],
            productStatus: 2,
        ));
    }
}
