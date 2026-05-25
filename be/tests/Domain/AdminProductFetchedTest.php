<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductFetched;
use MyVendor\BeMart\Be\Input\GetAdminProductInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class AdminProductFetchedTest extends TestCase
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

    public function testHappyPathReturnsFullDetail(): void
    {
        $final = ($this->becoming)(new GetAdminProductInput('admin-active-001'));

        $this->assertInstanceOf(AdminProductFetched::class, $final);
        $this->assertSame('admin-active-001', $final->productCode);
        $this->assertSame('管理画面用 商品A', $final->productName);
        $this->assertSame(3500, $final->price02);
        $this->assertSame(20, $final->stock);
        $this->assertSame(1, $final->productStatus);
        $this->assertSame('internal note A', $final->note);
        $this->assertSame('管理 active', $final->searchWord);
    }

    public function testAdminCanReadHiddenAndWithdrawnRows(): void
    {
        $hidden = ($this->becoming)(new GetAdminProductInput('admin-hidden-001'));
        $this->assertInstanceOf(AdminProductFetched::class, $hidden);
        $this->assertSame(2, $hidden->productStatus);

        $withdrawn = ($this->becoming)(new GetAdminProductInput('admin-withdrawn-001'));
        $this->assertInstanceOf(AdminProductFetched::class, $withdrawn);
        $this->assertSame(3, $withdrawn->productStatus);
    }

    public function testUnknownProductRaisesNotFound(): void
    {
        $this->expectException(ProductNotFoundException::class);
        ($this->becoming)(new GetAdminProductInput('does-not-exist'));
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetAdminProductInput('admin-active-001'));
    }

    public function testAuthzCheckRunsBeforeExistenceProbe(): void
    {
        // Anti-enumeration ladder: 403 BEFORE 404 even when productCode
        // does not resolve.
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetAdminProductInput('does-not-exist'));
    }
}
