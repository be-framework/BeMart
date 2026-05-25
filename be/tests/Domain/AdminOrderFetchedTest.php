<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderFetched;
use MyVendor\BeMart\Be\Input\GetAdminOrderInput;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 7 (goOrder) — Direct safe-read with AUTHZ + multi-aggregate fetch.
 *
 * Seeds one order for alice (a real seeded customer) so the customer-
 * summary side-read returns a populated projection. The seed past order
 * from Ray.FakeQuery fixture JSON covers the orphan-customer branch
 * (customerId='customer-001' has no row in customers.json).
 */
final class AdminOrderFetchedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ALICE_ORDER_NO = 'admin0000000000000000000000alice';

    private BecomingInterface $becoming;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
        $this->seedAliceOrder();
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
        $this->becoming = $this->injector->getInstance(BecomingInterface::class);
    }

    private function seedAliceOrder(): void
    {
        $storage->put(new FinalizedOrderEntity(
            orderNo: self::ALICE_ORDER_NO,
            preOrderId: 'admin0000000000000000000000pre00',
            customerId: self::ALICE_ID,
            paymentMethodId: 2,
            subtotal: 11000,
            deliveryFeeTotal: 600,
            charge: 0,
            discount: 0,
            tax: 1100,
            total: 12700,
            paymentTotal: 12700,
            addPoint: 127,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: '2026-05-15 10:00:00',
            paymentDate: '2026-05-15 10:00:00',
        ));
        $storage->putItems(self::ALICE_ORDER_NO, [
            new OrderItemEntity(
                orderNo: self::ALICE_ORDER_NO,
                productCode: 'sample-001',
                productName: 'サンプル商品 A',
                quantity: 2,
                unitPrice: 1200,
            ),
        ]);
    }

    public function testHappyPathReturnsOrderWithItemsAndCustomer(): void
    {
        $final = ($this->becoming)(new GetAdminOrderInput(
            orderNo: self::ALICE_ORDER_NO,
        ));

        $this->assertInstanceOf(AdminOrderFetched::class, $final);
        $this->assertSame(self::ALICE_ORDER_NO, $final->orderNo);
        $this->assertSame(self::ALICE_ID, $final->customerId);
        $this->assertSame(12700, $final->total);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $final->orderStatus);

        $this->assertSame(1, $final->itemCount);
        $this->assertSame('sample-001', $final->items[0]['productCode']);
        $this->assertSame(2, $final->items[0]['quantity']);

        // Customer summary surfaces — alice is a real seeded customer.
        $this->assertNotNull($final->customer);
        $this->assertSame(self::ALICE_ID, $final->customer['customerId']);
        $this->assertSame('alice@example.com', $final->customer['email']);
        $this->assertSame('山田', $final->customer['name01']);
    }

    public function testOrphanCustomerYieldsNullCustomer(): void
    {
        // The seed past order in Ray.FakeQuery fixture JSON uses
        // customerId='customer-001', which is NOT in customers.json —
        // an orphaned-customer order is still a real order, just
        // without a customer summary attached.
        $final = ($this->becoming)(new GetAdminOrderInput(
            orderNo: 'past0000000000000000000000000001',
        ));

        $this->assertInstanceOf(AdminOrderFetched::class, $final);
        $this->assertSame('past0000000000000000000000000001', $final->orderNo);
        $this->assertSame('customer-001', $final->customerId);
        $this->assertNull($final->customer);
        // The order still has its items.
        $this->assertGreaterThanOrEqual(1, $final->itemCount);
    }

    public function testUnknownOrderNoRaisesNotFound(): void
    {
        $this->expectException(OrderNotFoundException::class);
        ($this->becoming)(new GetAdminOrderInput(
            orderNo: 'nonexistentordernononononononono',
        ));
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);
        // Re-seed because rebindAdminSession rebuilds the injector.
        $this->seedAliceOrder();

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetAdminOrderInput(
            orderNo: self::ALICE_ORDER_NO,
        ));
    }

    public function testNoAdminSessionRefusesBeforeExistenceCheck(): void
    {
        // Anti-enumeration: admin-anonymous + unknown orderNo ⇒ 403 not 404.
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new GetAdminOrderInput(
            orderNo: 'nonexistentordernononononononono',
        ));
    }
}
