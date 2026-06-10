<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Settings-side coverage for EC-CUBE doUpdateOrderStatusList.
 *
 * The order-status Resource also has the older per-order status POST,
 * but the settings list update is routed as PUT from the legacy POST
 * alias so it can be tested independently.
 */
final class AdminOrderStatusSettingsResourceTest extends TestCase
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

    public function testOnPutOrderStatusListSurface(): void
    {
        $ro = $this->resource->put('page://self/admin/order-status', [
            'orderStatuses' => [
                ['id' => 1, 'name' => '新規受付'],
                ['id' => 3, 'name' => '注文取消し'],
            ],
            'orderStatusRows' => '1:新規受付,3:注文取消し',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('doUpdateOrderStatusList', $ro->body['transitionId']);
        $this->assertSame(2, $ro->body['count']);
        $this->assertSame('1:新規受付,3:注文取消し', $ro->body['orderStatusRows']);
    }

    public function testOnPutAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->put('page://self/admin/order-status', [
            'orderStatuses' => [['id' => 1, 'name' => '新規受付']],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
