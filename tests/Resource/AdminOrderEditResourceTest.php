<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminOrderEditForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the admin 受注編集 Order Tier-2 page.
 *
 * The resource is a thin GET renderer for EC-CUBE's `Order/edit.twig`
 * multi-panel editor. An empty orderNo renders a blank "new order"
 * editor (works with empty Fake storage); a known orderNo pre-fills;
 * an unknown orderNo is 404. The AUTHZ guard rejects anonymous admins.
 */
final class AdminOrderEditResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const ORDER_NO = 'admin000000000000000000edit0001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seedOrder(): void
    {
        $this->storage->put(new FinalizedOrderEntity(
            orderNo: self::ORDER_NO,
            preOrderId: 'admin000000000000000000edit0001p',
            customerId: '0123456789abcdef0123456789abcdef',
            paymentMethodId: 2,
            subtotal: 10000,
            deliveryFeeTotal: 500,
            charge: 300,
            discount: 0,
            tax: 1000,
            total: 11800,
            paymentTotal: 11800,
            addPoint: 118,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: '2026-05-15 10:00:00',
            paymentDate: '2026-05-15 10:00:00',
        ));
    }

    public function testOnGetNewRendersBlankEditor(): void
    {
        $ro = $this->resource->get('page://self/admin/order/edit');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminOrderEditForm::class, $ro->body['form']);
        $this->assertSame('', $ro->body['orderNo']);
        $this->assertNull($ro->body['order']);
        $this->assertSame([], $ro->body['items']);
    }

    public function testOnGetKnownOrderPreFillsEditor(): void
    {
        $this->seedOrder();

        $ro = $this->resource->get('page://self/admin/order/edit', ['orderNo' => self::ORDER_NO]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminOrderEditForm::class, $ro->body['form']);
        $this->assertSame(self::ORDER_NO, $ro->body['orderNo']);
        $this->assertSame(11800, $ro->body['order']['paymentTotal']);
    }

    public function testOnGetUnknownOrderReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/order/edit', ['orderNo' => 'nonexistent-zzz']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/order/edit');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
