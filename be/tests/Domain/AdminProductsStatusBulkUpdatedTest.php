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
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminProductsStatusBulkUpdatedTest extends TestCase
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

    public function testHappyPathFlipsMultipleProducts(): void
    {
        $final = ($this->becoming)(new AdminBulkUpdateProductStatusInput(
            productCodes: ['admin-active-001', 'admin-hidden-001'],
            productStatus: ProductEntity::STATUS_WITHDRAWN,
        ));

        $this->assertInstanceOf(AdminProductsStatusBulkUpdated::class, $final);
        $this->assertSame(2, $final->requestedCount);
        $this->assertSame(2, $final->changedCount);
        // FakeQuery fixtures are static; bulk status persistence is covered by the SQL suite.
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
        $this->markTestSkipped('Idempotent replay needs mutable persistence; covered by the SQL suite.');
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
