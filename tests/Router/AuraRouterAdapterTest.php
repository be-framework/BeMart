<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use Aura\Router\Map;
use Aura\Router\RouterContainer;
use BEAR\Sunday\Extension\Router\NullMatch;
use MyVendor\BeMart\Support\Router\AuraRouter;
use PHPUnit\Framework\TestCase;

final class AuraRouterAdapterTest extends TestCase
{
    private AuraRouter $router;

    protected function setUp(): void
    {
        $this->router = new AuraRouter(self::routerContainer());
    }

    public function testMatchesEcCubePathToBearRouterMatch(): void
    {
        $match = $this->router->match(
            ['_GET' => ['category_id' => '3'], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/products/list?category_id=3'],
        );

        $this->assertSame('get', $match->method);
        $this->assertSame('page://self/products', $match->path);
        $this->assertSame(['category_id' => '3'], $match->query);
    }

    public function testRenamesPathParameterForResource(): void
    {
        $match = $this->router->match(
            ['_GET' => ['category_id' => '10'], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/products/detail/42?category_id=10'],
        );

        $this->assertSame('page://self/product', $match->path);
        $this->assertSame(['productCode' => '42', 'category_id' => '10'], $match->query);
    }

    public function testRenamesQueryParameterForResource(): void
    {
        $match = $this->router->match(
            ['_GET' => ['id' => '9'], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin_content_block_edit?id=9'],
        );

        $this->assertSame('page://self/admin/block/block', $match->path);
        $this->assertSame(['blockId' => '9'], $match->query);
    }

    public function testUnsupportedMethodKeepsPathMatchForBearResource405(): void
    {
        $match = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/products/list'],
        );

        $this->assertSame('post', $match->method);
        $this->assertSame('page://self/products', $match->path);
    }

    public function testUnknownPathReturnsNullMatchForRouterCollectionFallback(): void
    {
        $match = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/no/such/path'],
        );

        $this->assertInstanceOf(NullMatch::class, $match);
    }

    public function testGeneratesWithAuraRouteName(): void
    {
        $this->assertSame('/products/detail/99', $this->router->generate('product_detail', ['id' => 99]));
        $this->assertFalse($this->router->generate('missing_route', []));
    }

    private static function routerContainer(): RouterContainer
    {
        $container = new RouterContainer();
        /** @var callable(Map): null $routes */
        $routes = require __DIR__ . '/../../config/aura-routes.php';
        $container->setMapBuilder($routes);

        return $container;
    }
}
