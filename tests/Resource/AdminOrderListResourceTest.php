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

use function dirname;

/**
 * Wave 7 (goOrderList) — admin-side order grid Resource.
 *
 * Mirrors AdminCustomerListResourceTest's `rebindAdminSession` helper.
 * The seed past order in Ray.FakeQuery fixture JSON gives us a stable
 * row to assert against without seeding extra fixtures.
 */
final class AdminOrderListResourceTest extends TestCase
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

    public function testOnGetReturnsOrderList(): void
    {
        $ro = $this->resource->get('page://self/admin/order-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(1, $ro->body['count']);
        $this->assertNotEmpty($ro->body['orders']);
        $this->assertSame(50, $ro->body['limit']);
        $this->assertSame(0, $ro->body['offset']);

        // Shallow projection — sensitive internals do not leak.
        foreach ($ro->body['orders'] as $row) {
            $this->assertArrayNotHasKey('preOrderId', $row);
            $this->assertArrayNotHasKey('addPoint', $row);
            $this->assertArrayNotHasKey('usePoint', $row);
            $this->assertArrayNotHasKey('paymentMethodId', $row);
        }
    }

    public function testOnGetWithPaginationKnobs(): void
    {
        $ro = $this->resource->get('page://self/admin/order-list', [
            'limit' => 1,
            'offset' => 0,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['limit']);
        $this->assertSame(0, $ro->body['offset']);
        $this->assertLessThanOrEqual(1, $ro->body['count']);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/order-list');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnGetWithInvalidLimitReturns400(): void
    {
        $ro = $this->resource->get('page://self/admin/order-list', [
            'limit' => 0,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }
}
