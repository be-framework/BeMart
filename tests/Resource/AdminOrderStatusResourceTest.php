<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminOrderStatusForm;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;

/**
 * Wave 7 (doUpdateOrderStatus) — admin-side status-flip Resource.
 *
 * Lives at `page://self/admin/order-status` (sub-resource of order).
 * Tests the happy path + idempotent replay + format rejection + CSRF +
 * AUTHZ failure + 404.
 */
final class AdminOrderStatusResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ORDER_NO = 'admin000000000000000statreso0001';

    private ResourceInterface $resource;
    private Injector $injector;
    private FakeFinalizedOrderStorage $storage;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
        $this->seedOrder();
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
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
        $this->storage = $this->injector->getInstance(FakeFinalizedOrderStorage::class);
    }

    private function seedOrder(): void
    {
        $this->storage->put(new FinalizedOrderEntity(
            orderNo: self::ORDER_NO,
            preOrderId: 'admin0000000000000statreso00001p',
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            subtotal: 8000,
            deliveryFeeTotal: 500,
            charge: 0,
            discount: 0,
            tax: 800,
            total: 9300,
            paymentTotal: 9300,
            addPoint: 93,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: '2026-05-15 10:00:00',
            paymentDate: '2026-05-15 10:00:00',
        ));
    }

    public function testOnGetReturnsOrderStatusSettingsForm(): void
    {
        $ro = $this->resource->get('page://self/admin/order-status');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminOrderStatusForm::class, $ro->body['form']);
        $this->assertNotEmpty($ro->body['orderStatuses']);
        $this->assertSame(1, $ro->body['orderStatuses'][0]['id']);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/order-status');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnPostHappyPathFlipsStatus(): void
    {
        $ro = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => self::ORDER_NO,
            'orderStatus' => FinalizedOrderEntity::STATUS_DELIVERED,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ORDER_NO, $ro->body['orderNo']);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $ro->body['previousStatus']);
        $this->assertSame(FinalizedOrderEntity::STATUS_DELIVERED, $ro->body['orderStatus']);
        $this->assertTrue($ro->body['changed']);

        $persisted = $this->storage->getByOrderNo(self::ORDER_NO);
        assert($persisted !== null);
        $this->assertSame(FinalizedOrderEntity::STATUS_DELIVERED, $persisted->orderStatus);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        // First flip.
        $this->resource->post('page://self/admin/order-status', [
            'orderNo' => self::ORDER_NO,
            'orderStatus' => FinalizedOrderEntity::STATUS_DELIVERED,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        // Replay with the same status.
        $ro = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => self::ORDER_NO,
            'orderStatus' => FinalizedOrderEntity::STATUS_DELIVERED,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['changed']);
        $this->assertSame(FinalizedOrderEntity::STATUS_DELIVERED, $ro->body['previousStatus']);
    }

    public function testOnPostOutOfRangeStatusReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => self::ORDER_NO,
            'orderStatus' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => self::ORDER_NO,
            'orderStatus' => FinalizedOrderEntity::STATUS_DELIVERED,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostUnknownOrderReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => 'nonexistentordernononononononono',
            'orderStatus' => FinalizedOrderEntity::STATUS_CANCEL,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);
        $this->seedOrder();

        $ro = $this->resource->post('page://self/admin/order-status', [
            'orderNo' => self::ORDER_NO,
            'orderStatus' => FinalizedOrderEntity::STATUS_CANCEL,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
