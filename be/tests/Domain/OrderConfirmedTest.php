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
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class OrderConfirmedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
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

    /**
     * Phase 3 enrichment — the confirm-screen pre-order (alice) carries a
     * resolvable customer fixture, so OrderConfirmed composes the
     * customer-info block, the line items (with product names resolved
     * from the product-class fixture) and the payment-method label.
     */
    public function testConfirmScreenProjectionIsComposed(): void
    {
        // aceface…a11ce — alice, クレジットカード(2): sample-001 ×2 @1200
        // + preorder-2026-spring-bag ×1 @13500.
        $final = ($this->becoming)(new ConfirmOrderInput(
            preOrderId: 'aceface0000000000000000000000000000a11ce',
            paymentMethodId: 2,
        ));

        $this->assertInstanceOf(OrderConfirmed::class, $final);

        // totals: subtotal 15900, tax 1590, delivery 800, total 18290.
        $this->assertSame(15900, $final->subtotal);
        $this->assertSame(1590, $final->tax);
        $this->assertSame(800, $final->deliveryFeeTotal);
        $this->assertSame(18290, $final->total);
        $this->assertSame(18290, $final->paymentTotal);

        // payment-method label resolved from the factory's selectable list.
        $this->assertSame('クレジットカード', $final->paymentMethodName);

        // customer-info block — read for the pre-order's customerId.
        $this->assertSame('山田', $final->customer['name01']);
        $this->assertSame('アリス', $final->customer['name02']);
        $this->assertSame('alice@example.com', $final->customer['email']);
        $this->assertSame('1500001', $final->customer['postalCode']);
        $this->assertSame(13, $final->customer['pref']);

        // line items — product names resolved from the product-class fixture.
        $this->assertCount(2, $final->items);
        $this->assertSame('サンプル商品 A', $final->items[0]['productName']);
        $this->assertSame(2, $final->items[0]['quantity']);
        $this->assertSame(2400, $final->items[0]['totalPrice']);
        $this->assertSame('2026 春予約バッグ', $final->items[1]['productName']);
        $this->assertSame(13500, $final->items[1]['totalPrice']);
    }

    public function testGuestConfirmScreenProjectionUsesOrderCustomerSnapshot(): void
    {
        // feedface… carries no customerId. Non-member checkout must render the
        // order-time buyer snapshot instead of requiring a customer row.
        $final = ($this->becoming)(new ConfirmOrderInput(
            preOrderId: 'feedfacefeedfacefeedfacefeedfacefeedface',
            paymentMethodId: 2,
        ));

        $this->assertInstanceOf(OrderConfirmed::class, $final);
        $this->assertSame(1200, $final->subtotal);
        $this->assertSame(120, $final->tax);
        $this->assertSame(500, $final->deliveryFeeTotal);
        $this->assertSame(1820, $final->total);
        $this->assertSame('クレジットカード', $final->paymentMethodName);
        $this->assertSame('非会員', $final->customer['name01']);
        $this->assertSame('花子', $final->customer['name02']);
        $this->assertSame('guest-confirm@example.com', $final->customer['email']);
        $this->assertSame('1000001', $final->customer['postalCode']);
        $this->assertSame(13, $final->customer['pref']);
        $this->assertCount(1, $final->items);
        $this->assertSame('サンプル商品 A', $final->items[0]['productName']);
        $this->assertSame(1200, $final->items[0]['totalPrice']);
    }

    public function testVerifyFailureBranchesToOrderConfirmFailed(): void
    {
        // paymentMethodId=9 routes to the fake payment failure handler.
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
