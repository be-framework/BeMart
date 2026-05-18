<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class CheckoutResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnPostCheckoutReturns201WithCompleteBody(): void
    {
        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $ro->body['orderNo']);
        $this->assertSame('customer-001', $ro->body['customerId']);
        $this->assertSame(2250, $ro->body['total']);
        $this->assertSame(2250, $ro->body['paymentTotal']);
        $this->assertSame(22, $ro->body['addPoint']);
        $this->assertSame('', $ro->body['completeMessage']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostUnknownPreOrderReturns404(): void
    {
        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'eeee00000000000000000000000000000000eeee',
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('eeee00000000000000000000000000000000eeee', $ro->body['preOrderId']);
    }

    public function testOnPostInsufficientStockReturns422(): void
    {
        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'bbbb00000000000000000000000000000000bbbb',
        ]);

        $this->assertSame(422, $ro->code);
        $this->assertStringContainsString('Insufficient', $ro->body['message']);
    }

    public function testOnPostPaymentDeclinedReturns422(): void
    {
        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'cccc00000000000000000000000000000000cccc',
        ]);

        $this->assertSame(422, $ro->code);
        $this->assertStringContainsString('declined', $ro->body['message']);
    }

    public function testOnPostMalformedPreOrderIdReturns400(): void
    {
        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'not-a-hex-id',
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertNotEmpty($ro->body['message']);
    }

    public function testClientSuppliedPaymentMethodIdIsIgnored(): void
    {
        // Pilot 5 F-2: even if a client tries to inject a different payment
        // method id via the request body, the gateway must be charged against
        // the persisted OrderEntity's paymentMethodId (2 for this preOrderId).
        // We can't directly assert on the gateway here, but the request must
        // still succeed — the extra key is silently ignored.
        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
            'paymentMethodId' => 9, // would otherwise trigger PaymentDeclinedException
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
    }
}
