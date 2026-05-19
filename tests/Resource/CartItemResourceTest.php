<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class CartItemResourceTest extends TestCase
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

    public function testOnPostAddsItemAndReturns201(): void
    {
        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame(2, $ro->body['adjustedQuantity']);
        $this->assertSame(2400, $ro->body['totalPrice']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostMissingProductReturns404(): void
    {
        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'missing-xyz',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('missing-xyz', $ro->body['productCode']);
    }

    public function testOnPutUpdatesQuantityAndReturns200(): void
    {
        // First add 2, then update to 5.
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $ro = $this->resource->put('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 5,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame(5, $ro->body['adjustedQuantity']);
        // sample-001 unitPrice is 1200; 5 * 1200 = 6000.
        $this->assertSame(6000, $ro->body['totalPrice']);
    }

    public function testOnPutMissingItemReturns404(): void
    {
        $ro = $this->resource->put('page://self/cart/item', [
            'productCode' => 'sample-002',
            'quantity' => 3,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('sample-002', $ro->body['productCode']);
    }

    public function testOnPutMissingCsrfReturns403(): void
    {
        $ro = $this->resource->put('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 3,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnDeleteRemovesItemAndReturns200(): void
    {
        // Add 2 items, then delete one.
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $ro = $this->resource->delete('page://self/cart/item', [
            'productCode' => 'sample-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame(0, $ro->body['totalPrice']);
    }

    public function testOnDeleteMissingItemReturns404(): void
    {
        $ro = $this->resource->delete('page://self/cart/item', [
            'productCode' => 'sample-002',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('sample-002', $ro->body['productCode']);
    }

    public function testOnDeleteMissingCsrfReturns403(): void
    {
        $ro = $this->resource->delete('page://self/cart/item', [
            'productCode' => 'sample-001',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostOutOfStockReturns409(): void
    {
        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'out-of-stock-test-001',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(409, $ro->code);
    }

    public function testOnPostInvalidQuantityReturns400(): void
    {
        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertNotEmpty($ro->body['message']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        // Phase B Slice 8: state-changing requests without a CSRF token are
        // rejected at the resource boundary, before the Becoming chain
        // even sees the payload.
        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 1,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostInvalidCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 1,
            'csrfToken' => 'not-the-real-token',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
