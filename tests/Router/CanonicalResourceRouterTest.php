<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use BEAR\Sunday\Extension\Router\RouterMatch;
use MyVendor\BeMart\Support\Router\CanonicalResourceRouter;
use PHPUnit\Framework\TestCase;

final class CanonicalResourceRouterTest extends TestCase
{
    private CanonicalResourceRouter $router;

    protected function setUp(): void
    {
        $this->router = new CanonicalResourceRouter();
    }

    public function testRootMapsToIndexResource(): void
    {
        $match = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'],
        );

        $this->assertSame('get', $match->method);
        $this->assertSame('page://self/index', $match->path);
        $this->assertSame([], $match->query);
    }

    public function testPathMapsDirectlyToBearResourcePath(): void
    {
        $match = $this->router->match(
            ['_GET' => ['limit' => '50'], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/product-list?limit=50'],
        );

        $this->assertSame('get', $match->method);
        $this->assertSame('page://self/admin/product-list', $match->path);
        $this->assertSame(['limit' => '50'], $match->query);
    }

    public function testLegacyUnderscoreUrlIsNotTranslated(): void
    {
        $match = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin_product_csv'],
        );

        $this->assertInstanceOf(RouterMatch::class, $match);
        $this->assertSame('get', $match->method);
        $this->assertSame('page://self/admin_product_csv', $match->path);
        $this->assertNotSame('page://self/admin/product-csv', $match->path);
    }

    public function testPostMethodOverrideDispatchesToPutAndRemovesMethodField(): void
    {
        $match = $this->router->match(
            ['_GET' => [], '_POST' => ['_method' => 'put', 'productCode' => 'sample-001']],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/product'],
        );

        $this->assertSame('put', $match->method);
        $this->assertSame('page://self/admin/product', $match->path);
        $this->assertSame(['productCode' => 'sample-001'], $match->query);
    }

    public function testPostMethodOverrideDispatchesToDeleteAndRemovesMethodField(): void
    {
        $match = $this->router->match(
            ['_GET' => ['productCode' => 'sample-001'], '_POST' => ['_method' => 'delete']],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/product?productCode=sample-001'],
        );

        $this->assertSame('delete', $match->method);
        $this->assertSame('page://self/admin/product', $match->path);
        $this->assertSame(['productCode' => 'sample-001'], $match->query);
    }

    public function testRouteNameGenerationIsDisabled(): void
    {
        $this->assertFalse($this->router->generate('product_detail', ['id' => 'sample-001']));
    }

    public function testCliArgvMapsToResourcePathAndQuery(): void
    {
        $match = $this->router->match(
            ['argv' => ['bin/fake.php', 'post', '/shopping/checkout?csrfToken=token-1'], '_GET' => [], '_POST' => []],
            ['argv' => ['bin/fake.php', 'post', '/shopping/checkout?csrfToken=token-1'], 'argc' => 3],
        );

        $this->assertSame('post', $match->method);
        $this->assertSame('page://self/shopping/checkout', $match->path);
        $this->assertSame(['csrfToken' => 'token-1'], $match->query);
    }

    public function testJsonBodyIsRequestQueryWhenPostSuperglobalIsEmpty(): void
    {
        $match = $this->router->match(
            ['_GET' => [], '_POST' => []],
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/entry',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_RAW_POST_DATA' => '{"email":"customer@example.com","csrfToken":"token-1"}',
            ],
        );

        $this->assertSame('post', $match->method);
        $this->assertSame('page://self/entry', $match->path);
        $this->assertSame(['email' => 'customer@example.com', 'csrfToken' => 'token-1'], $match->query);
    }
}
