<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\AppModule;
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
        $this->rebindSession(self::ALICE_ID);
    }

    private function rebindSession(string|null $customerId): void
    {
        $session = new FakeSession($customerId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)->toInstance($this->session);
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
        // FakeFinalizedOrderStorage's seed past order is owned by the
        // synthetic 'customer-001' which has no matching Customer
        // fixture row, so we can't use it directly here (the Final
        // raises Unauthenticated for unknown customerIds). Instead
        // we register a finalized order for alice and assert the
        // dashboard surfaces it.
        $storage = $this->injector->getInstance(FakeFinalizedOrderStorage::class);
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

        $ro = $this->resource->get('page://self/mypage');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(1, $ro->body['recentOrderCount']);
        $this->assertSame($aliceOrderNo, $ro->body['recentOrders'][0]['orderNo']);
        $this->assertSame(3800, $ro->body['recentOrders'][0]['total']);
        $this->assertSame(
            FinalizedOrderEntity::STATUS_NEW,
            $ro->body['recentOrders'][0]['orderStatus'],
        );
    }

    public function testOnGetUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->get('page://self/mypage');

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('ログイン', $ro->body['message']);
    }
}
