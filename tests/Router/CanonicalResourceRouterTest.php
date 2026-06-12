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


    public function testIdeaStoreLegacyProductDetailMapsToCanonicalProduct(): void
    {
        $match = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/products/detail/IDEA000001'],
        );

        $this->assertSame('get', $match->method);
        $this->assertSame('page://self/product', $match->path);
        $this->assertSame(['productCode' => 'IDEA000001'], $match->query);
    }

    public function testIdeaStoreLegacyProductListMapsToCanonicalProducts(): void
    {
        $match = $this->router->match(
            ['_GET' => ['name' => '収納'], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/products/list?name=%E5%8F%8E%E7%B4%8D'],
        );

        $this->assertSame('get', $match->method);
        $this->assertSame('page://self/products', $match->path);
        $this->assertSame(['name' => '収納'], $match->query);
    }

    public function testIdeaStoreLegacyAddCartMapsPathProductCode(): void
    {
        $match = $this->router->match(
            ['_GET' => [], '_POST' => ['quantity' => '1', 'csrfToken' => 'token-1']],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/products/add_cart/IDEA000001'],
        );

        $this->assertSame('post', $match->method);
        $this->assertSame('page://self/cart/item', $match->path);
        $this->assertSame([
            'quantity' => '1',
            'csrfToken' => 'token-1',
            'productCode' => 'IDEA000001',
        ], $match->query);
    }

    public function testIdeaStoreLegacyNonMemberMapsToCanonicalNonMember(): void
    {
        $match = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/shopping/nonmember'],
        );

        $this->assertSame('get', $match->method);
        $this->assertSame('page://self/shopping/non-member', $match->path);
        $this->assertSame([], $match->query);
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

    public function testPostBodyParamsWinOverQueryParams(): void
    {
        $match = $this->router->match(
            ['_GET' => ['productCode' => 'query-code'], '_POST' => ['productCode' => 'body-code', 'name' => 'BeMart product']],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/product?productCode=query-code'],
        );

        $this->assertSame('post', $match->method);
        $this->assertSame('page://self/admin/product', $match->path);
        $this->assertSame([
            'productCode' => 'body-code',
            'name' => 'BeMart product',
        ], $match->query);
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


    public function testCliRawJapaneseQueryKeepsUtf8Value(): void
    {
        $match = $this->router->match(
            ['argv' => ['bin/page.php', 'get', '/products?name=収納'], '_GET' => [], '_POST' => []],
            ['argv' => ['bin/page.php', 'get', '/products?name=収納'], 'argc' => 3],
        );

        $this->assertSame('get', $match->method);
        $this->assertSame('page://self/products', $match->path);
        $this->assertSame(['name' => '収納'], $match->query);
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
