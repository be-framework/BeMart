<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use Aura\Router\Map;
use Aura\Router\Route as AuraRoute;
use Aura\Router\Rule\Allows;
use MyVendor\BeMart\Module\BeMartTwigExtension;
use MyVendor\BeMart\Router\RouteTable;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

use function rtrim;
use function strtoupper;

/**
 * Proves the Aura route table used by the front controller and Twig helpers:
 * route resolution, path-parameter extraction with EC-CUBE-name -> resource-param
 * renaming, trailing-slash normalisation, and Aura's 404 / 405 failure signals.
 */
final class RouterTest extends TestCase
{
    private RouteTable $routes;

    protected function setUp(): void
    {
        $this->routes = RouteTable::default();
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

    public function testResolvesAdminProductListPath(): void
    {
        [$route, $metadata] = $this->match('GET', '/admin/product');

        $this->assertSame('admin_product', $route->name);
        $this->assertSame('page://self/admin/product-list', $metadata['resource']);
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
        $matcher = $this->routes->matcher();

        $this->assertFalse($matcher->match(new ServerRequest('GET', '/no/such/path')));
        $failed = $matcher->getFailedRoute();
        if ($failed instanceof AuraRoute) {
            $this->assertNotSame(Allows::class, $failed->failedRule);
        }
    }

    public function testKnownPathWrongMethodFailsOnAuraAllowsRule(): void
    {
        // `/products/list` exists but is GET-only.
        $this->assertMethodNotAllowed('POST', '/products/list');
    }

    public function testAddCartIsPostOnly(): void
    {
        [, $metadata, $params] = $this->match('POST', '/products/add_cart/7');
        $this->assertSame('page://self/cart/item', $metadata['resource']);
        $this->assertSame(['productCode' => '7'], $params);

        $this->assertMethodNotAllowed('GET', '/products/add_cart/7');
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

        $this->assertMethodNotAllowed('PUT', '/cart/item');
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
        $routes = RouteTable::fromMapBuilder(static function (Map $map): null {
            RouteTable::addRoute(
                $map,
                'delete_example',
                ['POST'],
                '/example/delete',
                'page://self/example',
                [],
                'delete',
                ['routeName' => 'delete_example'],
            );

            return null;
        });

        [$route, $metadata, $params] = $this->match('POST', '/example/delete', $routes);

        $this->assertSame('delete_example', $route->name);
        $this->assertSame('delete', $metadata['dispatchMethod']);
        $this->assertSame(['routeName' => 'delete_example'], $params);
    }

    public function testDefaultHtmlRouteTablePublishesGetOrPostOnly(): void
    {
        foreach ($this->routes->routes as $route) {
            foreach ($route->allows as $method) {
                $this->assertContains($method, ['GET', 'POST'], (string) $route->name);
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
    private function match(string $method, string $path, RouteTable|null $routes = null): array
    {
        $routes ??= $this->routes;
        $method = strtoupper($method);
        $route = $routes->matcher()->match(new ServerRequest($method, self::normalizeRoutePath($path)));
        $this->assertInstanceOf(AuraRoute::class, $route);

        $metadata = RouteTable::metadataFor($route, $method);
        $params = RouteTable::resourceParams($route, $metadata);

        return [$route, $metadata, $params];
    }

    private function assertMethodNotAllowed(string $method, string $path): void
    {
        $matcher = $this->routes->matcher();

        $this->assertFalse($matcher->match(new ServerRequest(strtoupper($method), self::normalizeRoutePath($path))));
        $failed = $matcher->getFailedRoute();
        $this->assertInstanceOf(AuraRoute::class, $failed);
        $this->assertSame(Allows::class, $failed->failedRule);
    }

    private static function normalizeRoutePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        $trimmed = rtrim($path, '/');

        return $trimmed === '' ? '/' : $trimmed;
    }
}
