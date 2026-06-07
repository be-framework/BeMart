<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for goOrderHistory.
 *
 * The seeded `customer-001` order from Ray.FakeQuery fixture JSON cannot
 * be reused at the BEAR layer (the customerId isn't backed by a
 * customer fixture, and the Resource flow round-trips through the
 * security listeners that key off the customer namespace). Instead we
 * follow `MypageResourceTest::testOnGetIncludesRecentOrders` — alice is
 * the canonical seeded customer, and we register a small batch of
 * finalized orders for her at setUp time so the pagination paths can
 * be exercised.
 */
#[\PHPUnit\Framework\Attributes\Group('stateful-sql-covered')]
final class OrderHistoryResourceTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ORDER_NO_NEWEST = 'alice000000000000000000000hist03';
    private const ORDER_NO_MIDDLE = 'alice000000000000000000000hist02';
    private const ORDER_NO_OLDEST = 'alice000000000000000000000hist01';

    private ResourceInterface $resource;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
        $this->rebindSession(self::ALICE_ID);
        $this->seedAliceOrders();
    }

    private function rebindSession(string|null $customerId): void
    {
        $session = new FakeSession($customerId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
    }

    private function seedAliceOrders(): void
    {
        // Three orders, oldest → newest by orderDate.
        $storage->put($this->makeOrder(self::ORDER_NO_OLDEST, '2026-05-01 09:00:00', 3000));
        $storage->put($this->makeOrder(self::ORDER_NO_MIDDLE, '2026-05-02 09:00:00', 5000));
        $storage->put($this->makeOrder(self::ORDER_NO_NEWEST, '2026-05-03 09:00:00', 7000));
    }

    private function makeOrder(string $orderNo, string $orderDate, int $subtotal): FinalizedOrderEntity
    {
        return new FinalizedOrderEntity(
            orderNo: $orderNo,
            preOrderId: $orderNo . 'pre',
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            subtotal: $subtotal,
            deliveryFeeTotal: 500,
            charge: 0,
            discount: 0,
            tax: (int) ($subtotal * 0.1),
            total: $subtotal + 500 + (int) ($subtotal * 0.1),
            paymentTotal: $subtotal + 500 + (int) ($subtotal * 0.1),
            addPoint: (int) ($subtotal * 0.01),
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: $orderDate,
            paymentDate: $orderDate,
        );
    }

    public function testOnGetReturnsAllOrders(): void
    {
        $ro = $this->resource->get('page://self/mypage/order-history');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame(3, $ro->body['orderCount']);
        $this->assertSame(50, $ro->body['limit']);
        $this->assertSame(0, $ro->body['offset']);

        // Newest-first ordering on the projection.
        $this->assertSame(self::ORDER_NO_NEWEST, $ro->body['orders'][0]['orderNo']);
        $this->assertSame(self::ORDER_NO_MIDDLE, $ro->body['orders'][1]['orderNo']);
        $this->assertSame(self::ORDER_NO_OLDEST, $ro->body['orders'][2]['orderNo']);
    }

    public function testOnGetWithDefaultParamsReturns200(): void
    {
        // Caller omits both pagination params — defaults must apply.
        $ro = $this->resource->get('page://self/mypage/order-history');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(3, $ro->body['orderCount']);
    }

    public function testOnGetHonoursPagination(): void
    {
        // Page 1: limit=1, offset=0 — only the newest row.
        $page1 = $this->resource->get('page://self/mypage/order-history', [
            'historyLimit' => 1,
            'offset' => 0,
        ]);
        $this->assertSame(Code::OK, $page1->code);
        $this->assertSame(1, $page1->body['orderCount']);
        $this->assertSame(self::ORDER_NO_NEWEST, $page1->body['orders'][0]['orderNo']);

        // Page 2: limit=1, offset=1 — the middle row.
        $page2 = $this->resource->get('page://self/mypage/order-history', [
            'historyLimit' => 1,
            'offset' => 1,
        ]);
        $this->assertSame(Code::OK, $page2->code);
        $this->assertSame(1, $page2->body['orderCount']);
        $this->assertSame(1, $page2->body['offset']);
        $this->assertSame(self::ORDER_NO_MIDDLE, $page2->body['orders'][0]['orderNo']);
    }

    public function testOnGetUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->get('page://self/mypage/order-history');

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('ログイン', $ro->body['message']);
    }
}
