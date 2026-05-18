<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\InsufficientStockException;
use MyVendor\BeMart\Be\Exception\PaymentDeclinedException;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedPreOrderAccessException;
use MyVendor\BeMart\Be\Final\CheckoutCompleted;
use MyVendor\BeMart\Be\Input\CheckoutInput;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\FakeCartStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Service\FakeMailer;
use MyVendor\BeMart\Be\Reason\Service\FakePaymentGateway;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function count;
use function dirname;

final class CheckoutCompletedTest extends TestCase
{
    private BecomingInterface $becoming;
    private FakeFinalizedOrderStorage $orderStorage;
    private FakeCartStorage $cartStorage;
    private FakeMailer $mailer;
    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        // Default session: customer-001 owns the `aaaa…` / `bbbb…` fixtures.
        // Tests using `cccc…` (customer-002) or asserting AUTHZ rejection
        // call rebindSession() explicitly.
        $this->rebindSession('customer-001');
    }

    /** Build a fresh injector with the given session customerId (null = anonymous). */
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

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->orderStorage = $injector->getInstance(FakeFinalizedOrderStorage::class);
        $this->cartStorage = $injector->getInstance(FakeCartStorage::class);
        $this->mailer = $injector->getInstance(FakeMailer::class);
        $this->gateway = $injector->getInstance(FakePaymentGateway::class);
    }

    public function testHappyPathConverges(): void
    {
        $final = ($this->becoming)(new CheckoutInput(
            preOrderId: 'aaaa00000000000000000000000000000000aaaa',
        ));

        $this->assertInstanceOf(CheckoutCompleted::class, $final);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $final->orderNo);
        $this->assertSame('customer-001', $final->customerId);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $final->orderStatus);
        // subtotal = 1500 * 1 = 1500; tax = 150; delivery = 600; total = 2250
        $this->assertSame(2250, $final->total);
        $this->assertSame(2250, $final->paymentTotal);
        // addPoint = total * 0.01 = 22 (integer cast)
        $this->assertSame(22, $final->addPoint);
        $this->assertSame('', $final->completeMessage);
    }

    public function testPersistsFinalizedOrder(): void
    {
        $final = ($this->becoming)(new CheckoutInput(
            preOrderId: 'aaaa00000000000000000000000000000000aaaa',
        ));

        assert($final instanceof CheckoutCompleted);
        $persisted = $this->orderStorage->getByOrderNo($final->orderNo);
        $this->assertInstanceOf(FinalizedOrderEntity::class, $persisted);
        $this->assertSame('aaaa00000000000000000000000000000000aaaa', $persisted->preOrderId);
        $this->assertSame(FinalizedOrderEntity::STATUS_NEW, $persisted->orderStatus);
        $this->assertSame(2250, $persisted->total);
    }

    public function testSendsExactlyOneConfirmationMail(): void
    {
        $before = count($this->mailer->sent());
        ($this->becoming)(new CheckoutInput(
            preOrderId: 'aaaa00000000000000000000000000000000aaaa',
        ));

        $this->assertCount($before + 1, $this->mailer->sent());
    }

    public function testCapturesPaymentExactlyOnceWithCorrectAmount(): void
    {
        $before = count($this->gateway->captures());
        ($this->becoming)(new CheckoutInput(
            preOrderId: 'aaaa00000000000000000000000000000000aaaa',
        ));

        $captures = $this->gateway->captures();
        $this->assertCount($before + 1, $captures);
        $last = $captures[count($captures) - 1];
        $this->assertSame('aaaa00000000000000000000000000000000aaaa', $last['preOrderId']);
        $this->assertSame(2, $last['paymentMethodId']);
        $this->assertSame(2250, $last['amount']);
    }

    public function testClearsSourceCart(): void
    {
        $this->assertNotNull(
            $this->cartStorage->getByPreOrderId('aaaa00000000000000000000000000000000aaaa'),
            'Pre-condition: cart fixture must exist for this preOrderId.',
        );

        ($this->becoming)(new CheckoutInput(
            preOrderId: 'aaaa00000000000000000000000000000000aaaa',
        ));

        $this->assertNull(
            $this->cartStorage->getByPreOrderId('aaaa00000000000000000000000000000000aaaa'),
            'Post-condition: cart must be cleared after checkout.',
        );
    }

    public function testUnknownPreOrderRejected(): void
    {
        $this->expectException(PreOrderNotFoundException::class);
        ($this->becoming)(new CheckoutInput(
            preOrderId: 'eeee00000000000000000000000000000000eeee',
        ));
    }

    public function testInsufficientStockRejected(): void
    {
        $this->expectException(InsufficientStockException::class);
        ($this->becoming)(new CheckoutInput(
            preOrderId: 'bbbb00000000000000000000000000000000bbbb',
        ));
    }

    public function testPaymentDeclinedRejected(): void
    {
        // `cccc…` belongs to customer-002 — rebind the session so we reach
        // PurchaseFlow (which simulates the decline) rather than tripping the
        // ownership check earlier in CheckoutPrepared.
        $this->rebindSession('customer-002');

        $this->expectException(PaymentDeclinedException::class);
        ($this->becoming)(new CheckoutInput(
            preOrderId: 'cccc00000000000000000000000000000000cccc',
        ));
    }

    public function testForeignCustomerRejectedWithAuthz(): void
    {
        // Phase B Slice 6 (Pilot 5 F-1): the requester is logged in but does
        // not own the `aaaa…` pre-order. CheckoutPrepared must reject *before*
        // any side effect runs (no payment capture, no mail, no order persist).
        $this->rebindSession('customer-999');

        $beforeCaptures = count($this->gateway->captures());
        $beforeMails = count($this->mailer->sent());

        try {
            ($this->becoming)(new CheckoutInput(
                preOrderId: 'aaaa00000000000000000000000000000000aaaa',
            ));
            $this->fail('Expected UnauthorizedPreOrderAccessException was not thrown.');
        } catch (UnauthorizedPreOrderAccessException) {
            // expected
        }

        $this->assertCount($beforeCaptures, $this->gateway->captures(), 'No payment must be captured on AUTHZ failure.');
        $this->assertCount($beforeMails, $this->mailer->sent(), 'No mail must be sent on AUTHZ failure.');
    }

    public function testAnonymousSessionRejectedWithAuthz(): void
    {
        // Anonymous (no logged-in customer) is also a mismatch: the pre-order
        // belongs to a real customer, the requester does not. Same exception,
        // same no-side-effect guarantee.
        $this->rebindSession(null);

        $this->expectException(UnauthorizedPreOrderAccessException::class);
        ($this->becoming)(new CheckoutInput(
            preOrderId: 'aaaa00000000000000000000000000000000aaaa',
        ));
    }
}
