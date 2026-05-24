<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductUpdated;
use MyVendor\BeMart\Be\Input\AdminUpdateProductInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminProductUpdatedTest extends TestCase
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
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testPartialUpdateOverwritesOnlySuppliedFields(): void
    {
        $final = ($this->becoming)(new AdminUpdateProductInput(
            productCode: 'admin-active-001',
            productName: '新しい名前',  // overwrite
            // price02, stock left untouched
        ));

        $this->assertInstanceOf(AdminProductUpdated::class, $final);
        $this->assertSame('admin-active-001', $final->productCode);
        $this->assertSame('新しい名前', $final->productName);
        // Original price persists.
        $this->assertSame(3500, $final->price02);
        $this->assertSame(20, $final->stock);
        // FakeQuery fixtures are static; persistence readback is covered by the SQL suite.
    }

    public function testStatusUpdateFromVisibleToHidden(): void
    {
        $final = ($this->becoming)(new AdminUpdateProductInput(
            productCode: 'admin-active-001',
            productStatus: 2,
        ));

        $this->assertInstanceOf(AdminProductUpdated::class, $final);
        $this->assertSame(2, $final->productStatus);
    }

    public function testUnknownProductRaisesNotFound(): void
    {
        $this->expectException(ProductNotFoundException::class);
        ($this->becoming)(new AdminUpdateProductInput(
            productCode: 'does-not-exist',
            productName: 'Whatever',
        ));
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminUpdateProductInput(
            productCode: 'admin-active-001',
            productName: 'whatever',
        ));
    }
}
