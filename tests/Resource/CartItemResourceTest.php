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
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('missing-xyz', $ro->body['productCode']);
    }

    public function testOnPostOutOfStockReturns409(): void
    {
        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'out-of-stock-test-001',
            'quantity' => 1,
        ]);

        $this->assertSame(409, $ro->code);
    }

    public function testOnPostInvalidQuantityReturns400(): void
    {
        $ro = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 0,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertNotEmpty($ro->body['message']);
    }
}
