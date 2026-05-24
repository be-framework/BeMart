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

final class IndexResourceTest extends TestCase
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

    public function testOnGetReturnsExpectedShape(): void
    {
        $ro = $this->resource->get('page://self/');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goTop', $ro->body['transitionId']);
        $this->assertSame([], $ro->body['fields']);
        $this->assertNull($ro->body['submitTo']);
        // staticContent is null on goTop — actual content (shopMessage /
        // newArrivals / recommendedProducts / categoryNav) is left as TODO.
        $this->assertNull($ro->body['staticContent']);
        $this->assertArrayHasKey('goLogin', $ro->body['links']);
        $this->assertArrayHasKey('goHelpAbout', $ro->body['links']);
        $this->assertArrayHasKey('goCart', $ro->body['links']);
    }
}
