<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderListFetched;
use MyVendor\BeMart\Be\Input\GetAdminOrderListInput;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function array_column;
use function dirname;

/**
 * Wave 7 (goOrderList) — Direct safe-read with cross-firewall AUTHZ +
 * pagination.
 *
 * The seed order from Ray.FakeQuery fixture JSON gives us one row out of
 * the box; the pagination test seeds two more so we can exercise
 * `limit` + `offset` end-to-end through the Becoming pipeline.
 */
final class AdminOrderListFetchedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private BecomingInterface $becoming;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
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
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $this->injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathReturnsOrders(): void
    {
        $final = ($this->becoming)(new GetAdminOrderListInput());

        $this->assertInstanceOf(AdminOrderListFetched::class, $final);
        $this->assertSame(50, $final->limit);
        $this->assertSame(0, $final->offset);
        $this->assertGreaterThanOrEqual(1, $final->count);

        $orderNos = array_column($final->orders, 'orderNo');
        $this->assertContains('past0000000000000000000000000001', $orderNos);

        // Shallow projection — sensitive internals do not leak.
        foreach ($final->orders as $row) {
            $this->assertArrayNotHasKey('preOrderId', $row);
            $this->assertArrayNotHasKey('addPoint', $row);
            $this->assertArrayNotHasKey('usePoint', $row);
            $this->assertArrayNotHasKey('paymentMethodId', $row);
        }
    }

    public function testPaginationHonoursLimitAndOffset(): void
    {
        $storage->put(new FinalizedOrderEntity(
            orderNo: 'admin000000000000000000000list01',
            preOrderId: 'admin0000000000000000000list0001p1',
            customerId: 'customer-002',
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
            orderDate: '2026-05-10 09:00:00',
            paymentDate: '2026-05-10 09:00:00',
        ));
        $storage->put(new FinalizedOrderEntity(
            orderNo: 'admin000000000000000000000list02',
            preOrderId: 'admin0000000000000000000list0002p2',
            customerId: 'customer-003',
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
            orderDate: '2026-05-11 09:00:00',
            paymentDate: '2026-05-11 09:00:00',
        ));

        $page1 = ($this->becoming)(new GetAdminOrderListInput(limit: 1, offset: 0));
        $this->assertInstanceOf(AdminOrderListFetched::class, $page1);
        $this->assertSame(1, $page1->count);
        $this->assertSame('admin000000000000000000000list02', $page1->orders[0]['orderNo']);

        $page2 = ($this->becoming)(new GetAdminOrderListInput(limit: 1, offset: 1));
        $this->assertInstanceOf(AdminOrderListFetched::class, $page2);
        $this->assertSame(1, $page2->count);
        $this->assertSame('admin000000000000000000000list01', $page2->orders[0]['orderNo']);
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetAdminOrderListInput());
    }
}
