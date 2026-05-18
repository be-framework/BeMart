<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\InsufficientStockException;
use MyVendor\BeMart\Be\Exception\PaymentDeclinedException;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Final\CheckoutCompleted;
use MyVendor\BeMart\Be\Input\CheckoutInput;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\FakeCartStorage;
use MyVendor\BeMart\Be\Reason\Query\FakeFinalizedOrderStorage;
use MyVendor\BeMart\Be\Reason\Service\FakeMailer;
use MyVendor\BeMart\Be\Reason\Service\FakePaymentGateway;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
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
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
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
            paymentMethodId: 2,
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
            paymentMethodId: 2,
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
            paymentMethodId: 2,
        ));

        $this->assertCount($before + 1, $this->mailer->sent());
    }

    public function testCapturesPaymentExactlyOnceWithCorrectAmount(): void
    {
        $before = count($this->gateway->captures());
        ($this->becoming)(new CheckoutInput(
            preOrderId: 'aaaa00000000000000000000000000000000aaaa',
            paymentMethodId: 2,
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
            paymentMethodId: 2,
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
            paymentMethodId: 2,
        ));
    }

    public function testInsufficientStockRejected(): void
    {
        $this->expectException(InsufficientStockException::class);
        ($this->becoming)(new CheckoutInput(
            preOrderId: 'bbbb00000000000000000000000000000000bbbb',
            paymentMethodId: 1,
        ));
    }

    public function testPaymentDeclinedRejected(): void
    {
        $this->expectException(PaymentDeclinedException::class);
        ($this->becoming)(new CheckoutInput(
            preOrderId: 'cccc00000000000000000000000000000000cccc',
            paymentMethodId: 9,
        ));
    }
}
