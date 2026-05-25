<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function array_column;
use function dirname;

final class AdminProductListResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

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
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsAdminProductList(): void
    {
        $ro = $this->resource->get('page://self/admin/product-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(5, $ro->body['count']);

        $codes = array_column($ro->body['products'], 'productCode');
        $this->assertContains('admin-active-001', $codes);
        $this->assertContains('admin-withdrawn-001', $codes);
    }

    public function testOnGetWithNameFilterNarrowsResults(): void
    {
        $ro = $this->resource->get('page://self/admin/product-list', [
            'nameKeyword' => '管理画面用',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(3, $ro->body['count']);
        $this->assertSame('管理画面用', $ro->body['filters']['nameKeyword']);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/product-list');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
