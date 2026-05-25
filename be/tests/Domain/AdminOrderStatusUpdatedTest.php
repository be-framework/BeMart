<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderStatusUpdated;
use MyVendor\BeMart\Be\Input\AdminUpdateOrderStatusInput;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;

/**
 * Wave 7 (doUpdateOrderStatus) — Direct idempotent admin status flip,
 * with format validation via the OrderStatus Semantic + idempotency
 * branch tested explicitly.
 */
final class AdminOrderStatusUpdatedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const TARGET_ORDER_NO = 'admin0000000000000000000000stat1';
    private const TARGET_CUSTOMER_ID = '0123456789abcdef0123456789abcdef';

    private BecomingInterface $becoming;
    private Injector $injector;
    private FakeFinalizedOrderStorage $storage;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
        $this->seedTargetOrder();
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
        $this->becoming = $this->injector->getInstance(BecomingInterface::class);
        $this->storage = $this->injector->getInstance(FakeFinalizedOrderStorage::class);
    }

    private function seedTargetOrder(): void
    {
        $this->storage->put(new FinalizedOrderEntity(
            orderNo: self::TARGET_ORDER_NO,
            preOrderId: 'admin0000000000000000000000statp',
            customerId: self::TARGET_CUSTOMER_ID,
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

    public function testHappyPathFlipsStatus(): void
    {
        $final = ($this->becoming)(new AdminUpdateOrderStatusInput(
            orderNo: self::TARGET_ORDER_NO,
            orderStatus: FinalizedOrderEntity::STATUS_DELIVERED,
        ));

        $this->assertInstanceOf(AdminOrderStatusUpdated::class, $final);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $final->previousStatus);
        $this->assertSame(FinalizedOrderEntity::STATUS_DELIVERED, $final->orderStatus);
        $this->assertTrue($final->changed);

        $persisted = $this->storage->getByOrderNo(self::TARGET_ORDER_NO);
        assert($persisted !== null);
        $this->assertSame(FinalizedOrderEntity::STATUS_DELIVERED, $persisted->orderStatus);
    }

    public function testIdempotentReplayIsNoOp(): void
    {
        // First flip moves it to DELIVERED.
        ($this->becoming)(new AdminUpdateOrderStatusInput(
            orderNo: self::TARGET_ORDER_NO,
            orderStatus: FinalizedOrderEntity::STATUS_DELIVERED,
        ));

        // Second call with the same value short-circuits.
        $replay = ($this->becoming)(new AdminUpdateOrderStatusInput(
            orderNo: self::TARGET_ORDER_NO,
            orderStatus: FinalizedOrderEntity::STATUS_DELIVERED,
        ));

        $this->assertInstanceOf(AdminOrderStatusUpdated::class, $replay);
        $this->assertSame(FinalizedOrderEntity::STATUS_DELIVERED, $replay->previousStatus);
        $this->assertSame(FinalizedOrderEntity::STATUS_DELIVERED, $replay->orderStatus);
        $this->assertFalse($replay->changed);
    }

    public function testOtherFieldsArePreservedAcrossFlip(): void
    {
        ($this->becoming)(new AdminUpdateOrderStatusInput(
            orderNo: self::TARGET_ORDER_NO,
            orderStatus: FinalizedOrderEntity::STATUS_CANCEL,
        ));

        $persisted = $this->storage->getByOrderNo(self::TARGET_ORDER_NO);
        assert($persisted !== null);
        $this->assertSame(self::TARGET_CUSTOMER_ID, $persisted->customerId);
        $this->assertSame(9300, $persisted->total);
        $this->assertSame(800, $persisted->tax);
        $this->assertSame('2026-05-15 10:00:00', $persisted->orderDate);
    }

    public function testOutOfRangeStatusRaisesSemanticVariableException(): void
    {
        // 2 is NOT in the EC-CUBE allowed set (1, 3-9).
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new AdminUpdateOrderStatusInput(
            orderNo: self::TARGET_ORDER_NO,
            orderStatus: 2,
        ));
    }

    public function testUnknownOrderNoRaisesNotFound(): void
    {
        $this->expectException(OrderNotFoundException::class);
        ($this->becoming)(new AdminUpdateOrderStatusInput(
            orderNo: 'nonexistentordernononononononono',
            orderStatus: FinalizedOrderEntity::STATUS_CANCEL,
        ));
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->seedTargetOrder();

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminUpdateOrderStatusInput(
            orderNo: self::TARGET_ORDER_NO,
            orderStatus: FinalizedOrderEntity::STATUS_CANCEL,
        ));
    }
}
