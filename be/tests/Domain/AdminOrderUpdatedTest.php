<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderUpdated;
use MyVendor\BeMart\Be\Input\AdminUpdateOrderInput;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;

/**
 * Wave 7 (doUpdateOrder) — Direct idempotent admin edit of editable
 * fields (discount / charge / usePoint), with mass-assignment safety
 * for non-editable fields verified via the persisted entity.
 */
final class AdminOrderUpdatedTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const TARGET_ORDER_NO = 'admin0000000000000000000000targ1';
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
            preOrderId: 'admin0000000000000000000000targp',
            customerId: self::TARGET_CUSTOMER_ID,
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

    public function testHappyPathOverwritesEditableFields(): void
    {
        $final = ($this->becoming)(new AdminUpdateOrderInput(
            orderNo: self::TARGET_ORDER_NO,
            discount: 1000,
            charge: 0,
            usePoint: 200,
        ));

        $this->assertInstanceOf(AdminOrderUpdated::class, $final);
        $this->assertSame(self::TARGET_ORDER_NO, $final->orderNo);
        $this->assertSame(1000, $final->discount);
        $this->assertSame(0, $final->charge);
        $this->assertSame(200, $final->usePoint);

        // Persisted shape matches.
        $persisted = $this->storage->getByOrderNo(self::TARGET_ORDER_NO);
        assert($persisted !== null);
        $this->assertSame(1000, $persisted->discount);
        $this->assertSame(0, $persisted->charge);
        $this->assertSame(200, $persisted->usePoint);
    }

    public function testNullsLeavePersistedValuesUntouched(): void
    {
        // All three editable fields supplied as null — Pilot 8 partial
        // update convention.
        $final = ($this->becoming)(new AdminUpdateOrderInput(
            orderNo: self::TARGET_ORDER_NO,
        ));

        $this->assertInstanceOf(AdminOrderUpdated::class, $final);
        $this->assertSame(0, $final->discount);
        $this->assertSame(300, $final->charge);
        $this->assertSame(0, $final->usePoint);
    }

    public function testNonEditableFieldsAreNotWritableViaThisTransition(): void
    {
        // The Input does not surface customerId / total / orderStatus —
        // those simply can't be reached. Confirming they round-trip
        // verbatim through the merge.
        ($this->becoming)(new AdminUpdateOrderInput(
            orderNo: self::TARGET_ORDER_NO,
            discount: 500,
        ));

        $persisted = $this->storage->getByOrderNo(self::TARGET_ORDER_NO);
        assert($persisted !== null);
        $this->assertSame(self::TARGET_CUSTOMER_ID, $persisted->customerId);
        $this->assertSame(11800, $persisted->total);
        $this->assertSame(11800, $persisted->paymentTotal);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $persisted->orderStatus);
        $this->assertSame('2026-05-15 10:00:00', $persisted->orderDate);
    }

    public function testIdempotentReplayIsSafe(): void
    {
        $first = ($this->becoming)(new AdminUpdateOrderInput(
            orderNo: self::TARGET_ORDER_NO,
            discount: 700,
        ));
        $second = ($this->becoming)(new AdminUpdateOrderInput(
            orderNo: self::TARGET_ORDER_NO,
            discount: 700,
        ));

        $this->assertSame(700, $first->discount);
        $this->assertSame(700, $second->discount);
        // Storage holds the same value.
        $persisted = $this->storage->getByOrderNo(self::TARGET_ORDER_NO);
        assert($persisted !== null);
        $this->assertSame(700, $persisted->discount);
    }

    public function testUnknownOrderNoRaisesNotFound(): void
    {
        $this->expectException(OrderNotFoundException::class);
        ($this->becoming)(new AdminUpdateOrderInput(
            orderNo: 'nonexistentordernononononononono',
            discount: 100,
        ));
    }

    public function testNoAdminSessionRaisesUnauthorizedAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->seedTargetOrder();

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new AdminUpdateOrderInput(
            orderNo: self::TARGET_ORDER_NO,
            discount: 100,
        ));
    }
}
