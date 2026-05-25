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

final class CartResourceTest extends TestCase
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

    public function testOnGetReturnsCartsForSessionPrefix(): void
    {
        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        // fixture has session-prefix-1_1 + session-prefix-1_2, both empty.
        $this->assertSame(2, $ro->body['cartCount']);
        $this->assertSame(0, $ro->body['totalPrice']);
        $this->assertCount(2, $ro->body['carts']);
        $this->assertSame('session-prefix-1_1', $ro->body['carts'][0]['cartKey']);
        $this->assertSame(1, $ro->body['carts'][0]['saleTypeId']);
    }

    public function testOnGetReturnsEmptyForUnknownPrefix(): void
    {
        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'no-such-session',
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(0, $ro->body['cartCount']);
        $this->assertSame([], $ro->body['carts']);
    }

    public function testOnGetDefaultsToFixturePrefix(): void
    {
        $ro = $this->resource->get('page://self/cart');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['cartCount']);
    }
}
