<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class MypageResourceTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    private ResourceInterface $resource;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
        $this->rebindSession(self::ALICE_ID);
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

    public function testOnGetReturnsCustomerSummary(): void
    {
        $ro = $this->resource->get('page://self/mypage');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame('alice@example.com', $ro->body['email']);
        $this->assertSame('山田', $ro->body['name01']);
        $this->assertSame('アリス', $ro->body['name02']);
        // Alice owns no past orders in the seed fixture — recent is empty.
        $this->assertSame(0, $ro->body['recentOrderCount']);
        $this->assertSame([], $ro->body['recentOrders']);
        $this->assertSame(0, $ro->body['favoriteCount']);
    }

    public function testOnGetAfterAddingFavorite(): void
    {
        $this->resource->post('page://self/mypage/favorite', [
            'productCode' => 'sample-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $ro = $this->resource->get('page://self/mypage');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['favoriteCount']);
    }

    public function testOnGetIncludesRecentOrders(): void
    {
        // Ray.FakeQuery fixture JSON's seed past order is owned by the
        // synthetic 'customer-001' which has no matching Customer
        // fixture row, so we can't use it directly here (the Final
        // raises Unauthenticated for unknown customerIds). Instead
        // we register a finalized order for alice and assert the
        // dashboard surfaces it.
        $aliceOrderNo = 'alice0000000000000000000000000001';
        $storage->put(new FinalizedOrderEntity(
            orderNo: $aliceOrderNo,
            preOrderId: 'alice00000000000000000000000000000000pre',
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            subtotal: 3000,
            deliveryFeeTotal: 500,
            charge: 0,
            discount: 0,
            tax: 300,
            total: 3800,
            paymentTotal: 3800,
            addPoint: 38,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: '2026-05-01 12:00:00',
            paymentDate: '2026-05-01 12:00:00',
        ));
        // Phase 3 enrichment — the dashboard's recentOrders rows carry an
        // `items` sub-array (read via OrderQuery::itemsByOrderNo).
        $storage->putItems($aliceOrderNo, [
            new OrderItemEntity(
                orderNo: $aliceOrderNo,
                productCode: 'sample-001',
                productName: 'サンプル商品 A',
                quantity: 2,
                unitPrice: 1200,
            ),
        ]);

        $ro = $this->resource->get('page://self/mypage');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(1, $ro->body['recentOrderCount']);
        $this->assertSame($aliceOrderNo, $ro->body['recentOrders'][0]['orderNo']);
        $this->assertSame(3800, $ro->body['recentOrders'][0]['total']);
        $this->assertSame(
            FinalizedOrderEntity::STATUS_NEW,
            $ro->body['recentOrders'][0]['orderStatus'],
        );
        // The order's line-item snapshot is surfaced under `items`.
        $this->assertCount(1, $ro->body['recentOrders'][0]['items']);
        $this->assertSame(
            'サンプル商品 A',
            $ro->body['recentOrders'][0]['items'][0]['productName'],
        );
        $this->assertSame(2, $ro->body['recentOrders'][0]['items'][0]['quantity']);
        $this->assertSame(1200, $ro->body['recentOrders'][0]['items'][0]['unitPrice']);
    }

    public function testOnGetUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->get('page://self/mypage');

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('ログイン', $ro->body['message']);
    }
}
