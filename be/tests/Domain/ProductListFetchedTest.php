<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ProductListFetched;
use MyVendor\BeMart\Be\Input\GetProductListInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function array_column;
use function dirname;

final class ProductListFetchedTest extends TestCase
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

    public function testHappyPathReturnsAllProducts(): void
    {
        $final = ($this->becoming)(new GetProductListInput());

        $this->assertInstanceOf(ProductListFetched::class, $final);
        $this->assertGreaterThanOrEqual(5, $final->count);

        $codes = array_column($final->products, 'productCode');
        // Pilot 1 seeds + Wave 8 admin seeds.
        $this->assertContains('sample-001', $codes);
        $this->assertContains('admin-active-001', $codes);
        $this->assertContains('admin-hidden-001', $codes);
        // Admin grid INCLUDES withdrawn rows.
        $this->assertContains('admin-withdrawn-001', $codes);
    }

    public function testNameFilterNarrowsResults(): void
    {
        $final = ($this->becoming)(new GetProductListInput(nameKeyword: '管理画面用'));

        $this->assertInstanceOf(ProductListFetched::class, $final);
        $this->assertSame(3, $final->count);
        $this->assertSame('管理画面用', $final->filters['nameKeyword']);
    }

    public function testLimitCapsResultSet(): void
    {
        $final = ($this->becoming)(new GetProductListInput(limit: 2));

        $this->assertInstanceOf(ProductListFetched::class, $final);
        $this->assertSame(2, $final->count);
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetProductListInput());
    }

    public function testInvalidLimitRaisesSemanticVariableException(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new GetProductListInput(limit: 0));
    }
}
