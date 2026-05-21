<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Router;

use MyVendor\BeMart\Router\Route;
use MyVendor\BeMart\Router\RouteMethodNotAllowedException;
use MyVendor\BeMart\Router\RouteNotFoundException;
use MyVendor\BeMart\Router\RouteTable;
use MyVendor\BeMart\Router\Router;
use PHPUnit\Framework\TestCase;

/**
 * Proves the front-controller router: route resolution, path-parameter
 * extraction (with EC-CUBE-name -> resource-param renaming), trailing-slash
 * normalisation, and the 404 / 405 failure modes.
 */
final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router(RouteTable::default());
    }

    public function testResolvesStorefrontTop(): void
    {
        $matched = $this->router->match('GET', '/');

        $this->assertSame('homepage', $matched->name);
        $this->assertSame('page://self/', $matched->resource);
        $this->assertSame([], $matched->params);
    }

    public function testResolvesProductListByEcCubePath(): void
    {
        $matched = $this->router->match('GET', '/products/list');

        $this->assertSame('product_list', $matched->name);
        $this->assertSame('page://self/products', $matched->resource);
    }

    public function testExtractsPathParamAndRenamesToResourceParam(): void
    {
        // EC-CUBE path param is `id`; the BEAR Product resource declares
        // `$productCode` — the router renames it on the way through.
        $matched = $this->router->match('GET', '/products/detail/42');

        $this->assertSame('product_detail', $matched->name);
        $this->assertSame('page://self/product', $matched->resource);
        $this->assertSame(['productCode' => '42'], $matched->params);
    }

    public function testNormalisesTrailingSlash(): void
    {
        $matched = $this->router->match('GET', '/cart/');

        $this->assertSame('cart', $matched->name);
    }

    public function testRootStaysRootWhenTrailingSlashStripped(): void
    {
        $this->assertSame('homepage', $this->router->match('GET', '/')->name);
    }

    public function testResolvesAdminRoute(): void
    {
        $matched = $this->router->match('GET', '/admin');

        $this->assertSame('admin_homepage', $matched->name);
        $this->assertSame('page://self/admin', $matched->resource);
    }

    public function testResolvesAdminProductListPath(): void
    {
        $matched = $this->router->match('GET', '/admin/product');

        $this->assertSame('admin_product', $matched->name);
        $this->assertSame('page://self/admin/product-list', $matched->resource);
    }

    public function testHelpTradeLawPathMapsToKebabResourceUri(): void
    {
        // The EC-CUBE route name `help_tradelaw` and path `/help/tradelaw`
        // resolve to the kebab-cased BEAR resource `help/trade-law`.
        $matched = $this->router->match('GET', '/help/tradelaw');

        $this->assertSame('page://self/help/trade-law', $matched->resource);
    }

    public function testUnknownPathRaises404(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->router->match('GET', '/no/such/path');
    }

    public function testKnownPathWrongMethodRaises405(): void
    {
        // `/products/list` exists but is GET-only.
        $this->expectException(RouteMethodNotAllowedException::class);
        $this->router->match('POST', '/products/list');
    }

    public function testAddCartIsPostOnly(): void
    {
        $matched = $this->router->match('POST', '/products/add_cart/7');
        $this->assertSame('page://self/cart/item', $matched->resource);
        $this->assertSame(['productCode' => '7'], $matched->params);

        $this->expectException(RouteMethodNotAllowedException::class);
        $this->router->match('GET', '/products/add_cart/7');
    }

    public function testMethodMatchingIsCaseInsensitive(): void
    {
        $this->assertSame('homepage', $this->router->match('get', '/')->name);
    }

    public function testCartItemServesPutAndDelete(): void
    {
        $this->assertSame('cart_handle_item', $this->router->match('PUT', '/cart/item')->name);
        $this->assertSame('cart_handle_item', $this->router->match('DELETE', '/cart/item')->name);
    }

    public function testGenerateIsInverseOfMatch(): void
    {
        // A route a template links to must be a route the router resolves:
        // generate() then match() must round-trip.
        $table = RouteTable::default();
        $route = $table->byName('product_detail');
        $this->assertInstanceOf(Route::class, $route);

        $url = $route->generate(['id' => 99]);
        $this->assertSame('/products/detail/99', $url);

        $matched = $this->router->match('GET', $url);
        $this->assertSame('product_detail', $matched->name);
        $this->assertSame(['productCode' => '99'], $matched->params);
    }

    public function testGeneratePutsNonPlaceholderParamsInQueryString(): void
    {
        $route = RouteTable::default()->byName('product_list');
        $this->assertInstanceOf(Route::class, $route);

        $this->assertSame('/products/list?category_id=3', $route->generate(['category_id' => 3]));
    }

    public function testPathParamValueIsUrlDecoded(): void
    {
        $matched = $this->router->match('GET', '/mypage/history/ORDER%2D001');
        $this->assertSame(['orderNo' => 'ORDER-001'], $matched->params);
    }
}
