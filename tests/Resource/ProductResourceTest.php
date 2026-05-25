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
use function file_exists;

final class ProductResourceTest extends TestCase
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

    public function testOnGetReturnsProductBody(): void
    {
        $ro = $this->resource->get('page://self/product', ['productCode' => 'sample-001']);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame('サンプル商品 A', $ro->body['productName']);
        $this->assertSame(1200, $ro->body['price02']);
        $this->assertSame(50, $ro->body['stock']);
    }

    public function testOnGetMissingProductReturns404(): void
    {
        $ro = $this->resource->get('page://self/product', ['productCode' => 'missing-xyz']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('missing-xyz', $ro->body['productCode']);
    }

    public function testSemanticLogIsWrittenAfterRequest(): void
    {
        $this->resource->get('page://self/product', ['productCode' => 'sample-001']);

        $logFile = dirname(__DIR__, 2) . '/var/log/bemart.json';
        $this->assertTrue(file_exists($logFile), 'DevBecoming should write a semantic log');
    }
}
