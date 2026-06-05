<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use Aura\Router\Map;
use Aura\Router\Route as AuraRoute;
use Aura\Router\RouterContainer;
use MyVendor\BeMart\Module\BeMartTwigExtension;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function is_array;
use function rtrim;
use function strtolower;
use function strtoupper;

/**
 * Proves the Aura route map used by the front controller and Twig helpers:
 * route resolution, path-parameter extraction with EC-CUBE-name -> resource-param
 * renaming, and trailing-slash normalisation. Aura does not gate by HTTP
 * method; BeMart metadata declares the HTTP methods each path can dispatch.
 */
final class RouterTest extends TestCase
{
    private RouterContainer $routes;

    protected function setUp(): void
    {
        $this->routes = self::routerContainer();
    }

    public function testResolvesStorefrontTop(): void
    {
        [$route, $metadata, $params] = $this->match('GET', '/');

        $this->assertSame('homepage', $route->name);
        $this->assertSame('page://self/', $metadata['resource']);
        $this->assertSame([], $params);
    }

    public function testResolvesProductListByEcCubePath(): void
    {
        [$route, $metadata] = $this->match('GET', '/products/list');

        $this->assertSame('product_list', $route->name);
        $this->assertSame('page://self/products', $metadata['resource']);
    }

    public function testExtractsPathParamAndRenamesToResourceParam(): void
    {
        // EC-CUBE path param is `id`; the BEAR Product resource declares
        // `$productCode` — route metadata renames it on the way through.
        [$route, $metadata, $params] = $this->match('GET', '/products/detail/42');

        $this->assertSame('product_detail', $route->name);
        $this->assertSame('page://self/product', $metadata['resource']);
        $this->assertSame(['productCode' => '42'], $params);
    }

    public function testNormalisesTrailingSlash(): void
    {
        [$route] = $this->match('GET', '/cart/');

        $this->assertSame('cart', $route->name);
    }

    public function testRootStaysRootWhenTrailingSlashStripped(): void
    {
        [$route] = $this->match('GET', '/');

        $this->assertSame('homepage', $route->name);
    }

    public function testResolvesAdminRoute(): void
    {
        [$route, $metadata] = $this->match('GET', '/admin');

        $this->assertSame('admin_homepage', $route->name);
        $this->assertSame('page://self/admin/index', $metadata['resource']);
    }

    public function testResolvesAdminProductResourcePath(): void
    {
        [$route, $metadata] = $this->match('GET', '/admin/product');

        $this->assertSame('admin_product', $route->name);
        $this->assertSame('page://self/admin/product', $metadata['resource']);
    }

    public function testHelpTradeLawPathMapsToKebabResourceUri(): void
    {
        // The EC-CUBE route name `help_tradelaw` and path `/help/tradelaw`
        // resolve to the kebab-cased BEAR resource `help/trade-law`.
        [, $metadata] = $this->match('GET', '/help/tradelaw');

        $this->assertSame('page://self/help/trade-law', $metadata['resource']);
    }

    public function testUnknownPathFailsAs404Candidate(): void
    {
        $matcher = $this->routes->getMatcher();

        $this->assertFalse($matcher->match(new ServerRequest('GET', '/no/such/path')));
    }

    public function testKnownPathWrongMethodHasNoDispatchMetadata(): void
    {
        $route = $this->routes->getMatcher()->match(new ServerRequest('POST', '/products/list'));
        $this->assertInstanceOf(AuraRoute::class, $route);

        $this->assertSame('product_list', $route->name);
        $methods = $route->extras['bemart']['methods'] ?? [];
        $this->assertIsArray($methods);
        $this->assertArrayNotHasKey('POST', $methods);
    }

    public function testAddCartIsPostOnly(): void
    {
        [, $metadata, $params] = $this->match('POST', '/products/add_cart/7');
        $this->assertSame('page://self/cart/item', $metadata['resource']);
        $this->assertSame(['productCode' => '7'], $params);

        $getRoute = $this->routes->getMatcher()->match(new ServerRequest('GET', '/products/add_cart/7'));
        $this->assertInstanceOf(AuraRoute::class, $getRoute);
        $this->assertSame('product_add_cart', $getRoute->name);
        $getMethods = $getRoute->extras['bemart']['methods'] ?? [];
        $this->assertIsArray($getMethods);
        $this->assertArrayNotHasKey('GET', $getMethods);
    }

    public function testMethodMatchingIsCaseInsensitive(): void
    {
        [$route] = $this->match('get', '/');

        $this->assertSame('homepage', $route->name);
    }

    public function testCartItemHtmlEndpointUsesGetAndPostOnly(): void
    {
        [$get, $getMetadata] = $this->match('GET', '/cart/item');
        $this->assertSame('cart_handle_item', $get->name);
        $this->assertSame('page://self/cart', $getMetadata['resource']);
        $this->assertSame('get', $getMetadata['dispatchMethod']);

        [$post, $postMetadata] = $this->match('POST', '/cart/item');
        $this->assertSame('cart_handle_item', $post->name);
        $this->assertSame('page://self/cart/item', $postMetadata['resource']);
        $this->assertSame('post', $postMetadata['dispatchMethod']);

        $put = $this->routes->getMatcher()->match(new ServerRequest('PUT', '/cart/item'));
        $this->assertInstanceOf(AuraRoute::class, $put);
        $this->assertSame('cart_handle_item', $put->name);
        $putMethods = $put->extras['bemart']['methods'] ?? [];
        $this->assertIsArray($putMethods);
        $this->assertArrayNotHasKey('PUT', $putMethods);
    }

    public function testEntryFormPostsToRegisterResource(): void
    {
        [$route, $metadata] = $this->match('POST', '/entry');

        $this->assertSame('entry', $route->name);
        $this->assertSame('page://self/entry', $metadata['resource']);
    }

    public function testEntryConfirmPageIsDisplayable(): void
    {
        [$route, $metadata] = $this->match('GET', '/entry/confirm');

        $this->assertSame('entry_confirm', $route->name);
        $this->assertSame('page://self/entry/confirm', $metadata['resource']);
    }

    public function testPostRouteCanDispatchToInternalResourceMethod(): void
    {
        $routes = new RouterContainer();
        $routes->setMapBuilder(static function (Map $map): null {
            $map->route('delete_example', '/example/delete', 'delete_example')
                ->extras([
                    'bemart' => [
                        'methods' => [
                            'POST' => [
                                'resource' => 'page://self/example',
                                'dispatchMethod' => 'delete',
                                'paramMap' => [],
                                'defaults' => ['routeName' => 'delete_example'],
                                'queryParamMap' => [],
                            ],
                        ],
                    ],
                ]);

            return null;
        });

        [$route, $metadata, $params] = $this->match('POST', '/example/delete', $routes);

        $this->assertSame('delete_example', $route->name);
        $this->assertSame('delete', $metadata['dispatchMethod']);
        $this->assertSame(['routeName' => 'delete_example'], $params);
    }

    public function testDefaultHtmlRouteMapPublishesGetOrPostOnly(): void
    {
        foreach ($this->routes->getMap()->getRoutes() as $route) {
            /** @var mixed $methods */
            $methods = $route->extras['bemart']['methods'] ?? [];
            $this->assertIsArray($methods);
            foreach (array_keys($methods) as $method) {
                $this->assertContains($method, ['GET', 'POST', 'PUT', 'DELETE'], (string) $route->name);
            }
        }
    }

    public function testGenerateIsInverseOfMatch(): void
    {
        // A route a template links to must be a route Aura resolves:
        // generate() then match() must round-trip.
        $urls = new BeMartTwigExtension($this->routes);

        $url = $urls->path('product_detail', ['id' => 99]);
        $this->assertSame('/products/detail/99', $url);

        [$route, , $params] = $this->match('GET', $url);
        $this->assertSame('product_detail', $route->name);
        $this->assertSame(['productCode' => '99'], $params);
    }

    public function testGeneratePutsNonPlaceholderParamsInQueryString(): void
    {
        $urls = new BeMartTwigExtension($this->routes);

        $this->assertSame('/products/list?category_id=3', $urls->path('product_list', ['category_id' => 3]));
    }

    public function testPathParamValueIsUrlDecoded(): void
    {
        [, , $params] = $this->match('GET', '/mypage/history/ORDER%2D001');
        $this->assertSame(['orderNo' => 'ORDER-001'], $params);
    }

    /**
     * @return array{0: AuraRoute, 1: array<string, mixed>, 2: array<string, string>}
     */
    private function match(string $method, string $path, RouterContainer|null $routes = null): array
    {
        $routes ??= $this->routes;
        $method = strtoupper($method);
        $route = $routes->getMatcher()->match(new ServerRequest($method, self::normalizeRoutePath($path)));
        $this->assertInstanceOf(AuraRoute::class, $route);

        $metadata = self::routeMetadata($route, $method);
        $params = self::resourceParams($route, $metadata);

        return [$route, $metadata, $params];
    }

    /** @return array<string, mixed> */
    private static function routeMetadata(AuraRoute $route, string $method): array
    {
        /** @var mixed $metadata */
        $metadata = $route->extras['bemart']['methods'][$method] ?? null;
        self::assertIsArray($metadata);

        /** @var array<string, mixed> */
        return $metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, string>
     */
    private static function resourceParams(AuraRoute $route, array $metadata): array
    {
        /** @var array<string, string> $defaults */
        $defaults = $metadata['defaults'];
        /** @var array<string, string> $paramMap */
        $paramMap = $metadata['paramMap'];
        $params = $defaults;
        /** @var array<string, mixed> $attributes */
        $attributes = $route->attributes;
        foreach ($attributes as $key => $value) {
            $resourceParam = $paramMap[$key] ?? $key;
            $params[$resourceParam] = (string) $value;
        }

        return $params;
    }

    private static function normalizeRoutePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        $trimmed = rtrim($path, '/');

        return $trimmed === '' ? '/' : $trimmed;
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
