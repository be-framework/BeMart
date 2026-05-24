<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;
use function is_array;

/**
 * Phase 3 enrichment — covers the Confirm resource's JSON body.
 *
 * The Confirm resource drives the doConfirmOrder Be Becoming chain
 * (ConfirmOrderInput → … → OrderConfirmed). It needs no session — the
 * chain resolves the pre-order by id — so the AppModule default binding
 * suffices. `onGet` with no args defaults to the alice confirm-screen
 * pre-order fixture (`aceface…a11ce`).
 */
final class ShoppingConfirmResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsEnrichedConfirmBody(): void
    {
        $ro = $this->resource->get('page://self/shopping/confirm');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertIsArray($ro->body);

        // totals — sample-001 ×2 @1200 + 2026 春予約バッグ ×1 @13500.
        $this->assertSame(15900, $ro->body['subtotal']);
        $this->assertSame(1590, $ro->body['tax']);
        $this->assertSame(800, $ro->body['deliveryFeeTotal']);
        $this->assertSame(18290, $ro->body['total']);
        $this->assertSame(18290, $ro->body['paymentTotal']);

        // payment method label.
        $this->assertSame('クレジットカード', $ro->body['paymentMethodName']);
    }

    public function testOnGetCarriesCustomerInfoBlock(): void
    {
        $ro = $this->resource->get('page://self/shopping/confirm');

        $this->assertIsArray($ro->body);
        $this->assertIsArray($ro->body['customer']);

        $customer = $ro->body['customer'];
        $this->assertSame('山田', $customer['name01']);
        $this->assertSame('アリス', $customer['name02']);
        $this->assertSame('alice@example.com', $customer['email']);
        $this->assertSame('1500001', $customer['postalCode']);
        $this->assertSame(13, $customer['pref']);
    }

    public function testOnGetCarriesLineItems(): void
    {
        $ro = $this->resource->get('page://self/shopping/confirm');

        $this->assertIsArray($ro->body);
        $this->assertTrue(is_array($ro->body['items']));
        $this->assertCount(2, $ro->body['items']);

        $this->assertSame('サンプル商品 A', $ro->body['items'][0]['productName']);
        $this->assertSame(2, $ro->body['items'][0]['quantity']);
        $this->assertSame(2400, $ro->body['items'][0]['totalPrice']);
        $this->assertSame('2026 春予約バッグ', $ro->body['items'][1]['productName']);
        $this->assertSame(13500, $ro->body['items'][1]['totalPrice']);
    }

    public function testOnGetUnknownPreOrderReturns404(): void
    {
        $ro = $this->resource->get('page://self/shopping/confirm', [
            'preOrderId' => '0000000000000000000000000000000000000000',
            'paymentMethodId' => 1,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetMalformedPreOrderIdReturns400(): void
    {
        $ro = $this->resource->get('page://self/shopping/confirm', [
            'preOrderId' => 'not-hex',
            'paymentMethodId' => 1,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnGetVerifyFailureRedirectsToShoppingError(): void
    {
        // paymentMethodId 9 routes to the verify-failing fake; the chain
        // produces OrderConfirmFailed → the resource bounces to /error.
        $ro = $this->resource->get('page://self/shopping/confirm', [
            'preOrderId' => 'deadbeefcafe1234567890abcdef01234567890c',
            'paymentMethodId' => 9,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/shopping/error', $ro->headers['Location']);
    }
}
