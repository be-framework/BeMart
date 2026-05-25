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

final class ShoppingLoginResourceTest extends TestCase
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

    public function testOnGetReturnsExpectedShape(): void
    {
        $ro = $this->resource->get('page://self/shopping/login');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goShoppingLogin', $ro->body['transitionId']);
        $this->assertSame([], $ro->body['fields']);
        $this->assertNull($ro->body['submitTo']);
        $this->assertNull($ro->body['staticContent']);
        // Three exits per ALPS #ShoppingLogin: member login, registration, non-member.
        $this->assertArrayHasKey('doLogin', $ro->body['links']);
        $this->assertArrayHasKey('goCustomerRegistration', $ro->body['links']);
        $this->assertArrayHasKey('goShoppingNonMember', $ro->body['links']);
    }
}
