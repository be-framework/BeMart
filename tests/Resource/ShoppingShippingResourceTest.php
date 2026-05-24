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

final class ShoppingShippingResourceTest extends TestCase
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

    public function testOnGetShippingReturnsExpectedShape(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goShoppingShipping', $ro->body['transitionId']);
        $this->assertSame(['shippingAddressId', 'csrfToken'], $ro->body['fields']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/shopping/shipping', $ro->body['submitTo']['href']);
        // Address data lookup is a Wave-future TODO — empty list for now.
        $this->assertSame([], $ro->body['addresses']);
        $this->assertNull($ro->body['csrfToken']);
    }

    public function testOnGetShippingEditReturnsExpectedShape(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping-edit');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goShoppingShippingEdit', $ro->body['transitionId']);
        $this->assertContains('name01', $ro->body['fields']);
        $this->assertContains('postalCode', $ro->body['fields']);
        $this->assertContains('addr01', $ro->body['fields']);
        $this->assertContains('csrfToken', $ro->body['fields']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/shopping/shipping-edit', $ro->body['submitTo']['href']);
        $this->assertNull($ro->body['csrfToken']);
    }

    public function testOnGetShippingMultipleReturnsExpectedShape(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping-multiple');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goShoppingShippingMultiple', $ro->body['transitionId']);
        $this->assertSame([], $ro->body['fields']);
        $this->assertNull($ro->body['submitTo']);
        // Cart-item × address allocation is a Wave-future TODO.
        $this->assertSame([], $ro->body['cartItems']);
        $this->assertSame([], $ro->body['addresses']);
        $this->assertSame('page://self/shopping', $ro->body['links']['goShopping']);
    }
}
