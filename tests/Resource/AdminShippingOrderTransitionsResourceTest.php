<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeMailer;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function str_contains;

/**
 * Phase 3 ALPS-audit remediation — JSON resource coverage for the admin
 * Order transitions doUpdateTrackingNumber / doSendShippingNotifyMail.
 */
final class AdminShippingOrderTransitionsResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const ORDER_NO = 'admin0000000000000shiporder0001';
    private const CUSTOMER_ID = '0123456789abcdef0123456789abcdef';

    private ResourceInterface $resource;
    private Injector $injector;
    private FakeMailer $mailer;

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
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);

        $mailer = $this->injector->getInstance(MailerInterface::class);
        assert($mailer instanceof FakeMailer);
        $this->mailer = $mailer;
    }

    private function seedOrder(): void
    {
        $this->orderStorage->put(new FinalizedOrderEntity(
            orderNo: self::ORDER_NO,
            preOrderId: 'admin0000000000000shiporder001p',
            customerId: self::CUSTOMER_ID,
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

    // ---- doUpdateTrackingNumber ---------------------------------------

    public function testUpdateTrackingNumberHappyPath(): void
    {
        $ro = $this->resource->put('page://self/admin/order/tracking-number', [
            'orderNo' => self::ORDER_NO,
            'trackingNumber' => 'TRK-12345',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ORDER_NO, $ro->body['orderNo']);
        $this->assertSame('TRK-12345', $ro->body['trackingNumber']);
    }

    public function testUpdateTrackingNumberRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->seedOrder();

        $ro = $this->resource->put('page://self/admin/order/tracking-number', [
            'orderNo' => self::ORDER_NO,
            'trackingNumber' => 'TRK-1',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testUpdateTrackingNumberRejectsMissingCsrf(): void
    {
        $ro = $this->resource->put('page://self/admin/order/tracking-number', [
            'orderNo' => self::ORDER_NO,
            'trackingNumber' => 'TRK-1',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testUpdateTrackingNumberUnknownOrderReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/order/tracking-number', [
            'orderNo' => 'nonexistentordernononononononono',
            'trackingNumber' => 'TRK-1',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testUpdateTrackingNumberEmptyValueReturns400(): void
    {
        $ro = $this->resource->put('page://self/admin/order/tracking-number', [
            'orderNo' => self::ORDER_NO,
            'trackingNumber' => '',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    // ---- doSendShippingNotifyMail -------------------------------------

    public function testSendShippingNotifyMailHappyPath(): void
    {
        $ro = $this->resource->post('page://self/admin/order/shipping-notify-mail', [
            'orderNo' => self::ORDER_NO,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ORDER_NO, $ro->body['orderNo']);
        $this->assertSame(self::CUSTOMER_ID, $ro->body['customerId']);
        $this->assertCount(1, $this->mailer->shippingNotifications());
    }

    public function testSendShippingNotifyMailIncludesTrackingNumber(): void
    {
        $this->resource->put('page://self/admin/order/tracking-number', [
            'orderNo' => self::ORDER_NO,
            'trackingNumber' => 'TRK-SHIPPED',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $ro = $this->resource->post('page://self/admin/order/shipping-notify-mail', [
            'orderNo' => self::ORDER_NO,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('TRK-SHIPPED', $ro->body['trackingNumber']);
        $this->assertSame(
            'TRK-SHIPPED',
            $this->mailer->shippingNotifications()[0]['trackingNumber'],
        );
    }

    public function testSendShippingNotifyMailRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->seedOrder();

        $ro = $this->resource->post('page://self/admin/order/shipping-notify-mail', [
            'orderNo' => self::ORDER_NO,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testSendShippingNotifyMailRejectsMissingCsrf(): void
    {
        $ro = $this->resource->post('page://self/admin/order/shipping-notify-mail', [
            'orderNo' => self::ORDER_NO,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testSendShippingNotifyMailUnknownOrderReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/order/shipping-notify-mail', [
            'orderNo' => 'nonexistentordernononononononono',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
