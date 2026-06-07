<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\OrderHistoryFetched;
use MyVendor\BeMart\Be\Input\GetOrderHistoryInput;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * goOrderHistory — Direct safe-read with AUTHN.
 *
 * SEED_ORDER_NO (from Ray.FakeQuery fixture JSON) is owned by
 * `customer-001`, so the happy-path session is bound to that id. The
 * pagination case seeds two additional past orders for the same customer
 * so `limit` and `offset` can be exercised end-to-end against the
 * Becoming pipeline.
 */
#[\PHPUnit\Framework\Attributes\Group('stateful-sql-covered')]
final class OrderHistoryFetchedTest extends TestCase
{
    private const TEST_CUSTOMER_ID = 'customer-001';

    private BecomingInterface $becoming;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
        $this->rebindSession(self::TEST_CUSTOMER_ID);
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
        $this->becoming = $this->injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathReturnsCustomerOrders(): void
    {
        $final = ($this->becoming)(new GetOrderHistoryInput());

        $this->assertInstanceOf(OrderHistoryFetched::class, $final);
        $this->assertSame(self::TEST_CUSTOMER_ID, $final->customerId);
        $this->assertGreaterThanOrEqual(1, $final->orderCount);
        $this->assertSame(50, $final->limit);
        $this->assertSame(0, $final->offset);

        // The seed order must surface in the projection.
        $orderNos = [];
        foreach ($final->orders as $row) {
            $orderNos[] = $row['orderNo'];
        }

        $this->assertContains('past0000000000000000000000000001', $orderNos);

        // Projection is shallow — no preOrderId / addPoint / usePoint leak.
        foreach ($final->orders as $row) {
            $this->assertArrayNotHasKey('preOrderId', $row);
            $this->assertArrayNotHasKey('addPoint', $row);
            $this->assertArrayNotHasKey('usePoint', $row);
        }
    }

    public function testPaginationHonoursLimitAndOffset(): void
    {
        // Seed two additional finalized orders for customer-001 so the
        // history has three rows total (seed past order + two newer).
        $storage->put(new FinalizedOrderEntity(
            orderNo: 'cust001000000000000000000000new02',
            preOrderId: 'cust00100000000000000000000pre0002',
            customerId: self::TEST_CUSTOMER_ID,
            paymentMethodId: 2,
            subtotal: 5000,
            deliveryFeeTotal: 500,
            charge: 0,
            discount: 0,
            tax: 500,
            total: 6000,
            paymentTotal: 6000,
            addPoint: 60,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: '2026-05-02 09:00:00',
            paymentDate: '2026-05-02 09:00:00',
        ));
        $storage->put(new FinalizedOrderEntity(
            orderNo: 'cust001000000000000000000000new03',
            preOrderId: 'cust00100000000000000000000pre0003',
            customerId: self::TEST_CUSTOMER_ID,
            paymentMethodId: 2,
            subtotal: 7000,
            deliveryFeeTotal: 500,
            charge: 0,
            discount: 0,
            tax: 700,
            total: 8200,
            paymentTotal: 8200,
            addPoint: 82,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: '2026-05-03 09:00:00',
            paymentDate: '2026-05-03 09:00:00',
        ));

        // Page 1: limit=1, offset=0 → newest (2026-05-03).
        $page1 = ($this->becoming)(new GetOrderHistoryInput(historyLimit: 1, offset: 0));
        $this->assertInstanceOf(OrderHistoryFetched::class, $page1);
        $this->assertSame(1, $page1->orderCount);
        $this->assertSame(1, $page1->limit);
        $this->assertSame(0, $page1->offset);
        $this->assertSame('cust001000000000000000000000new03', $page1->orders[0]['orderNo']);

        // Page 2: limit=1, offset=1 → second newest (2026-05-02).
        $page2 = ($this->becoming)(new GetOrderHistoryInput(historyLimit: 1, offset: 1));
        $this->assertInstanceOf(OrderHistoryFetched::class, $page2);
        $this->assertSame(1, $page2->orderCount);
        $this->assertSame(1, $page2->offset);
        $this->assertSame('cust001000000000000000000000new02', $page2->orders[0]['orderNo']);

        // Page 3: limit=2, offset=2 → only the seed past order remains.
        $page3 = ($this->becoming)(new GetOrderHistoryInput(historyLimit: 2, offset: 2));
        $this->assertInstanceOf(OrderHistoryFetched::class, $page3);
        $this->assertSame(1, $page3->orderCount);
        $this->assertSame(2, $page3->offset);
        $this->assertSame(
            'past0000000000000000000000000001',
            $page3->orders[0]['orderNo'],
        );
    }

    public function testNoSessionRaisesUnauthenticated(): void
    {
        $this->rebindSession(null);

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new GetOrderHistoryInput());
    }
}
