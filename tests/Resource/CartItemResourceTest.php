<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class CartItemResourceTest extends TestCase
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
        // Static fake fixture already contains sample-001 x3 for the
        // HTML cart scenario; adding 2 more yields 5 * 1200.
        $this->assertSame(6000, $ro->body['totalPrice']);
        $this->assertSame('/cart', $ro->headers['Location']);
    }

    public function testOnPostOperationAddUsesBrowserFormAndRedirectsToCart(): void
    {
        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'operation' => 'add',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/cart', $ro->headers['Location']);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame(1, $ro->body['adjustedQuantity']);
    }

    public function testOnPostMissingProductReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\ProductClassNotFoundException::class);

        $this->resource->post('page://self/cart/item', [
            'productCode' => 'missing-xyz',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
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

    public function testOnPostOperationUpUsesBrowserFormAndRedirectsToCart(): void
    {
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'operation' => 'up',
            'quantity' => 4,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/cart', $ro->headers['Location']);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame(4, $ro->body['adjustedQuantity']);
    }

    public function testOnPostOperationDownUsesBrowserFormAndRedirectsToCart(): void
    {
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 3,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'operation' => 'down',
            'quantity' => 2,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/cart', $ro->headers['Location']);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame(2, $ro->body['adjustedQuantity']);
    }

    public function testOnPostOperationRemoveUsesBrowserFormAndRedirectsToCart(): void
    {
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'operation' => 'remove',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/cart', $ro->headers['Location']);
        $this->assertSame('sample-001', $ro->body['productCode']);
    }

    public function testOnPutMissingItemReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\CartItemNotInCartException::class);

        $this->resource->put('page://self/cart/item', [
            'productCode' => 'sample-002',
            'quantity' => 3,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
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
        $this->expectException(\MyVendor\BeMart\Be\Exception\CartItemNotInCartException::class);

        $this->resource->delete('page://self/cart/item', [
            'productCode' => 'sample-002',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostOutOfStockReturns409(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\OutOfStockException::class);

        $this->resource->post('page://self/cart/item', [
            'productCode' => 'out-of-stock-test-001',
            'quantity' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostInvalidQuantityReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

}
