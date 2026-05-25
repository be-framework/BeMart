<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\PaymentMethodIdFormatException;
use MyVendor\BeMart\Be\Exception\PreOrderIdFormatException;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Final\OrderConfirmed;
use MyVendor\BeMart\Be\Final\OrderConfirmFailed;
use MyVendor\BeMart\Be\Input\ConfirmOrderInput;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class OrderConfirmedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testCashOnDeliverySucceeds(): void
    {
        // sample-001 ×2 @1200 = subtotal 2400, tax 240, deliveryFee 500.
        $final = ($this->becoming)(new ConfirmOrderInput(
            preOrderId: 'deadbeefcafe1234567890abcdef01234567890a',
            paymentMethodId: 1,
        ));

        $this->assertInstanceOf(OrderConfirmed::class, $final);
        $this->assertSame('deadbeefcafe1234567890abcdef01234567890a', $final->preOrderId);
        $this->assertSame(1, $final->paymentMethodId);
        $this->assertSame(2400, $final->subtotal);
        $this->assertSame(240, $final->tax);
        $this->assertSame(500, $final->deliveryFeeTotal);
        $this->assertSame(0, $final->charge);
        $this->assertSame(0, $final->discount);
        $this->assertSame(3140, $final->total); // 2400 + 240 + 500
        $this->assertSame(3140, $final->paymentTotal);
        $this->assertSame(31, $final->addPoint); // 1% of 3140
        $this->assertSame(0, $final->usePoint);
    }

    public function testCreditCardSucceeds(): void
    {
        // preorder-2026-spring-bag ×1 @8000 = subtotal 8000, tax 800, delivery 800.
        $final = ($this->becoming)(new ConfirmOrderInput(
            preOrderId: 'deadbeefcafe1234567890abcdef01234567890b',
            paymentMethodId: 2,
        ));

        $this->assertInstanceOf(OrderConfirmed::class, $final);
        $this->assertSame(2, $final->paymentMethodId);
        $this->assertSame(8000, $final->subtotal);
        $this->assertSame(800, $final->tax);
        $this->assertSame(800, $final->deliveryFeeTotal);
        $this->assertSame(9600, $final->total);
        $this->assertSame(96, $final->addPoint);
    }

    public function testVerifyFailureBranchesToOrderConfirmFailed(): void
    {
        // paymentMethodId=9 routes to FakeVerifyFailing.
        $final = ($this->becoming)(new ConfirmOrderInput(
            preOrderId: 'deadbeefcafe1234567890abcdef01234567890c',
            paymentMethodId: 9,
        ));

        $this->assertInstanceOf(OrderConfirmFailed::class, $final);
        $this->assertSame('deadbeefcafe1234567890abcdef01234567890c', $final->preOrderId);
        $this->assertSame(9, $final->paymentMethodId);
        $this->assertSame(['Card validation failed'], $final->errors);
    }

    public function testMissingPreOrderThrows(): void
    {
        $this->expectException(PreOrderNotFoundException::class);
        ($this->becoming)(new ConfirmOrderInput(
            preOrderId: '0000000000000000000000000000000000000000',
            paymentMethodId: 1,
        ));
    }

    public function testInvalidPreOrderIdRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new ConfirmOrderInput(
                preOrderId: 'not-hex',
                paymentMethodId: 1,
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                PreOrderIdFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    public function testInvalidPaymentMethodIdRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new ConfirmOrderInput(
                preOrderId: 'deadbeefcafe1234567890abcdef01234567890a',
                paymentMethodId: 0,
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                PaymentMethodIdFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }
}
