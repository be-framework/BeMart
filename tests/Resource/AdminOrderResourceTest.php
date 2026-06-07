<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;

/**
 * Wave 7 (goOrder + doUpdateOrder) — admin-side order detail Resource.
 *
 * Seeds an order owned by alice (a real seeded customer) so the GET path
 * surfaces a populated customer summary. PUT covers happy-path edits
 * + CSRF rejection + AUTHZ failure + 404 + mass-assignment safety.
 */
#[\PHPUnit\Framework\Attributes\Group('stateful-sql-covered')]
final class AdminOrderResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ORDER_NO = 'admin000000000000000000reso0001';

    private ResourceInterface $resource;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
        $this->seedOrder();
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

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
    }

    private function seedOrder(): void
    {
        $this->storage->put(new FinalizedOrderEntity(
            orderNo: self::ORDER_NO,
            preOrderId: 'admin000000000000000000reso0001p',
            customerId: self::ALICE_ID,
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
        $this->storage->putItems(self::ORDER_NO, [
            new OrderItemEntity(
                orderNo: self::ORDER_NO,
                productCode: 'sample-001',
                productName: 'サンプル商品 A',
                quantity: 2,
                unitPrice: 1200,
            ),
        ]);
    }

    public function testOnGetReturnsOrderDetail(): void
    {
        $ro = $this->resource->get('page://self/admin/order', [
            'orderNo' => self::ORDER_NO,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ORDER_NO, $ro->body['orderNo']);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame(11800, $ro->body['total']);
        $this->assertSame(1, $ro->body['itemCount']);
        $this->assertNotNull($ro->body['customer']);
        $this->assertSame('alice@example.com', $ro->body['customer']['email']);
    }

    public function testOnGetUnknownOrderReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/order', [
            'orderNo' => 'nonexistentordernononononononono',
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertStringContainsString('注文', $ro->body['message']);
    }

    public function testOnGetWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);
        $this->seedOrder();

        $ro = $this->resource->get('page://self/admin/order', [
            'orderNo' => self::ORDER_NO,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnPutHappyPathUpdatesEditableFields(): void
    {
        $ro = $this->resource->put('page://self/admin/order', [
            'orderNo' => self::ORDER_NO,
            'discount' => 1000,
            'charge' => 0,
            'usePoint' => 200,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ORDER_NO, $ro->body['orderNo']);
        $this->assertSame(1000, $ro->body['discount']);
        $this->assertSame(0, $ro->body['charge']);
        $this->assertSame(200, $ro->body['usePoint']);

        // Persisted shape matches.
        $persisted = $this->storage->byOrderNo(self::ORDER_NO);
        assert($persisted !== null);
        $this->assertSame(1000, $persisted->discount);
    }

    public function testOnPutPreservesNonEditableFields(): void
    {
        $this->resource->put('page://self/admin/order', [
            'orderNo' => self::ORDER_NO,
            'discount' => 500,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $persisted = $this->storage->byOrderNo(self::ORDER_NO);
        assert($persisted !== null);
        // Mass-assignment safety — these are not body fields.
        $this->assertSame(self::ALICE_ID, $persisted->customerId);
        $this->assertSame(11800, $persisted->total);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $persisted->orderStatus);
    }

    public function testOnPutMissingCsrfReturns403(): void
    {
        $ro = $this->resource->put('page://self/admin/order', [
            'orderNo' => self::ORDER_NO,
            'discount' => 1000,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPutUnknownOrderReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/order', [
            'orderNo' => 'nonexistentordernononononononono',
            'discount' => 1000,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPutWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);
        $this->seedOrder();

        $ro = $this->resource->put('page://self/admin/order', [
            'orderNo' => self::ORDER_NO,
            'discount' => 1000,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
