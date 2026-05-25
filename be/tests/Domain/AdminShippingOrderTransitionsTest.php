<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ShippingNotifyMailSent;
use MyVendor\BeMart\Be\Final\TrackingNumberUpdated;
use MyVendor\BeMart\Be\Input\SendShippingNotifyMailInput;
use MyVendor\BeMart\Be\Input\UpdateTrackingNumberInput;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\ShippingAddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Phase 3 ALPS-audit remediation — the admin Order transitions
 * doUpdateTrackingNumber / doSendShippingNotifyMail (domain layer).
 */
final class AdminShippingOrderTransitionsTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const TARGET_ORDER_NO = 'admin0000000000000000000000targ1';
    private const TARGET_CUSTOMER_ID = '0123456789abcdef0123456789abcdef';

    private BecomingInterface $becoming;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->markTestSkipped('Stateful write/readback scenario is covered by the SQL suite.');
        $this->bindAs(self::TEST_ADMIN_ID);
        $this->seedOrder();
    }

    /**
     * Bind ShippingAddressStorageInterface and its concrete Fake to the
     * SAME instance so the Final (which resolves the interface) and the
     * test introspection (which reads the concrete class) see one store
     * — the Ray.Di linked-binding gotcha documented in AppModule.
     */
    private function bindAs(string|null $adminId): void
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

    private function seedOrder(): void
    {
        $orders->put(new FinalizedOrderEntity(
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

    // ---- doUpdateTrackingNumber ---------------------------------------

    public function testUpdateTrackingNumberPersistsValue(): void
    {
        $final = ($this->becoming)(new UpdateTrackingNumberInput(
            orderNo: self::TARGET_ORDER_NO,
            trackingNumber: 'TRK-1234567890',
        ));

        $this->assertInstanceOf(TrackingNumberUpdated::class, $final);
        $this->assertSame(self::TARGET_ORDER_NO, $final->orderNo);
        $this->assertSame('TRK-1234567890', $final->trackingNumber);

        $shipping = $this->injector->getInstance(ShippingAddressStorageInterface::class);
        $this->assertSame(
            'TRK-1234567890',
            $shipping->item(self::TARGET_ORDER_NO)->trackingNumber,
        );
    }

    public function testUpdateTrackingNumberIsIdempotent(): void
    {
        $first = ($this->becoming)(new UpdateTrackingNumberInput(
            self::TARGET_ORDER_NO,
            'TRK-SAME',
        ));
        $second = ($this->becoming)(new UpdateTrackingNumberInput(
            self::TARGET_ORDER_NO,
            'TRK-SAME',
        ));

        $this->assertSame('TRK-SAME', $first->trackingNumber);
        $this->assertSame('TRK-SAME', $second->trackingNumber);
    }

    public function testUpdateTrackingNumberRejectsUnknownOrder(): void
    {
        $this->expectException(OrderNotFoundException::class);
        ($this->becoming)(new UpdateTrackingNumberInput(
            orderNo: 'nonexistentordernononononononono',
            trackingNumber: 'TRK-1',
        ));
    }

    public function testUpdateTrackingNumberRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $this->seedOrder();

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new UpdateTrackingNumberInput(
            orderNo: self::TARGET_ORDER_NO,
            trackingNumber: 'TRK-1',
        ));
    }

    public function testUpdateTrackingNumberRejectsEmptyValue(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new UpdateTrackingNumberInput(
            orderNo: self::TARGET_ORDER_NO,
            trackingNumber: '',
        ));
    }

    // ---- doSendShippingNotifyMail -------------------------------------

    public function testSendShippingNotifyMailDispatchesMail(): void
    {
        $final = ($this->becoming)(new SendShippingNotifyMailInput(
            orderNo: self::TARGET_ORDER_NO,
        ));

        $this->assertInstanceOf(ShippingNotifyMailSent::class, $final);
        $this->assertSame(self::TARGET_ORDER_NO, $final->orderNo);
        $this->assertSame(self::TARGET_CUSTOMER_ID, $final->customerId);
        $this->assertNull($final->trackingNumber);

        $mailer = $this->injector->getInstance(FakeMailer::class);
        $this->assertCount(1, $mailer->shippingNotifications);
        $this->assertSame(
            self::TARGET_ORDER_NO,
            $mailer->shippingNotifications[0]['order']->orderNo,
        );
    }

    public function testSendShippingNotifyMailIncludesTrackingNumberWhenSet(): void
    {
        ($this->becoming)(new UpdateTrackingNumberInput(
            self::TARGET_ORDER_NO,
            'TRK-SHIPPED',
        ));

        $final = ($this->becoming)(new SendShippingNotifyMailInput(self::TARGET_ORDER_NO));

        $this->assertSame('TRK-SHIPPED', $final->trackingNumber);
        $mailer = $this->injector->getInstance(FakeMailer::class);
        $this->assertSame(
            'TRK-SHIPPED',
            $mailer->shippingNotifications[0]['trackingNumber'],
        );
    }

    public function testSendShippingNotifyMailIsUnsafeAndReplaysMail(): void
    {
        ($this->becoming)(new SendShippingNotifyMailInput(self::TARGET_ORDER_NO));
        ($this->becoming)(new SendShippingNotifyMailInput(self::TARGET_ORDER_NO));

        // unsafe — each call fires a fresh mail.
        $mailer = $this->injector->getInstance(FakeMailer::class);
        $this->assertCount(2, $mailer->shippingNotifications);
    }

    public function testSendShippingNotifyMailRejectsUnknownOrder(): void
    {
        $this->expectException(OrderNotFoundException::class);
        ($this->becoming)(new SendShippingNotifyMailInput(
            orderNo: 'nonexistentordernononononononono',
        ));
    }

    public function testSendShippingNotifyMailRejectsAnonymousAdmin(): void
    {
        $this->bindAs(null);
        $this->seedOrder();

        $this->expectException(UnauthorizedAdminAccessException::class);
        ($this->becoming)(new SendShippingNotifyMailInput(self::TARGET_ORDER_NO));
    }
}
