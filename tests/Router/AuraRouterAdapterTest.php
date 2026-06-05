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

    public function testUnsupportedMethodReturnsNullMatch(): void
    {
        $match = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/products/list'],
        );

        $this->assertInstanceOf(NullMatch::class, $match);
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

    public function testMypageDeleteGetRoutesToConcreteListResources(): void
    {
        $delivery = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/mypage/delivery/delete'],
        );
        $favorite = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/mypage/favorite/delete'],
        );

        $this->assertSame('get', $delivery->method);
        $this->assertSame('page://self/mypage/address-list', $delivery->path);
        $this->assertSame('get', $favorite->method);
        $this->assertSame('page://self/mypage/favorite-list', $favorite->path);
    }

    public function testShoppingShippingPostsRouteToConcreteResources(): void
    {
        $shipping = $this->router->match(
            ['_GET' => [], '_POST' => ['address' => '1']],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/shopping/shipping'],
        );
        $shippingEdit = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/shopping/shipping/edit'],
        );
        $shippingMultiple = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/shopping/shipping/multiple'],
        );
        $shippingMultipleEdit = $this->router->match(
            ['_GET' => [], '_POST' => []],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/shopping/shipping/multiple/edit'],
        );

        $this->assertSame('post', $shipping->method);
        $this->assertSame('page://self/shopping/shipping', $shipping->path);
        $this->assertSame(['shippingAddressId' => '1'], $shipping->query);
        $this->assertSame('post', $shippingEdit->method);
        $this->assertSame('page://self/shopping/shipping-edit', $shippingEdit->path);
        $this->assertSame('post', $shippingMultiple->method);
        $this->assertSame('page://self/shopping/shipping-multiple', $shippingMultiple->path);
        $this->assertSame('post', $shippingMultipleEdit->method);
        $this->assertSame('page://self/shopping/shipping-multiple-edit', $shippingMultipleEdit->path);
    }

    public function testNonHardActionRedirectAuditTargetsRouteToConcreteResources(): void
    {
        $targets = [
            ['GET', '/admin_order_bulk_delete', [], [], 'page://self/admin/order-list', 'get'],
            ['GET', '/admin_product_bulk_product_status', [], [], 'page://self/admin/product-list', 'get'],
            ['GET', '/admin_product_class_category_sort_no_move?class_name_id=1', ['class_name_id' => '1'], [], 'page://self/admin/class-category/class-category-list', 'get'],
            ['GET', '/admin_product_class_category_visibility?class_name_id=1', ['class_name_id' => '1'], [], 'page://self/admin/class-category/class-category-list', 'get'],
            ['GET', '/admin_product_class_name_sort_no_move', [], [], 'page://self/admin/class-name/class-name-list', 'get'],
            ['GET', '/admin_product_product_copy?id=sample-001', ['id' => 'sample-001'], [], 'page://self/admin/product/edit', 'get'],
            ['GET', '/admin_product_tag_sort_no_move', [], [], 'page://self/admin/tag/tag-list', 'get'],
            ['POST', '/admin_setting_shop_calendar', [], ['title' => '元日', 'holiday' => '2026-01-01'], 'page://self/admin/calendar', 'post'],
            ['GET', '/admin_setting_shop_calendar_delete', [], [], 'page://self/admin/calendar', 'get'],
            ['POST', '/admin_setting_shop_calendar_delete', [], ['id' => '1'], 'page://self/admin/calendar', 'delete'],
            ['POST', '/admin_setting_shop_calendar_new', [], ['title' => '祝日', 'holiday' => '2026-02-11'], 'page://self/admin/calendar', 'post'],
            ['GET', '/admin_setting_shop_delivery_sort_no_move', [], [], 'page://self/admin/delivery/delivery-list', 'get'],
            ['GET', '/admin_setting_shop_delivery_visibility', [], [], 'page://self/admin/delivery/delivery-list', 'get'],
            ['GET', '/admin_setting_shop_mail_delete', [], [], 'page://self/admin/mail-template', 'get'],
            ['POST', '/admin_setting_shop_mail_delete', [], ['id' => '1'], 'page://self/admin/mail-template', 'delete'],
            ['POST', '/admin_setting_shop_order_status', [], ['orderStatusRows' => '1:新規受付'], 'page://self/admin/order-status', 'put'],
            ['GET', '/admin_setting_shop_payment_sort_no_move', [], [], 'page://self/admin/payment/payment-list', 'get'],
            ['GET', '/admin_setting_shop_payment_visible', [], [], 'page://self/admin/payment/payment-list', 'get'],
            ['POST', '/admin_setting_shop_tradelaw', [], ['tradeLawBody' => '販売業者: BeMart'], 'page://self/admin/trade-law', 'post'],
            ['GET', '/admin_setting_system_member_down', [], [], 'page://self/admin/member-list', 'get'],
            ['POST', '/admin_setting_system_member_down', [], ['id' => 'ad000000000000000000000000000002', 'sortNo' => '3'], 'page://self/admin/sort-no-move', 'put'],
            ['GET', '/admin_setting_system_member_up', [], [], 'page://self/admin/member-list', 'get'],
            ['POST', '/admin_setting_system_member_up', [], ['id' => 'ad000000000000000000000000000002', 'sortNo' => '1'], 'page://self/admin/sort-no-move', 'put'],
            ['GET', '/admin_shipping_notify_mail?id=order-001', ['id' => 'order-001'], [], 'page://self/admin/order/edit', 'get'],
            ['GET', '/admin_shipping_update_order_status', [], [], 'page://self/admin/order-list', 'get'],
            ['GET', '/admin_shipping_update_tracking_number?id=order-001', ['id' => 'order-001'], [], 'page://self/admin/order/edit', 'get'],
            ['GET', '/admin_store_template_download?id=default', ['id' => 'default'], [], 'page://self/admin/template/template-list', 'get'],
        ];

        foreach ($targets as [$method, $uri, $get, $post, $path, $dispatch]) {
            $match = $this->router->match(
                ['_GET' => $get, '_POST' => $post],
                ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri],
            );

            $this->assertNotSame('page://self/admin/action-redirect', $match->path, $method . ' ' . $uri);
            $this->assertSame($path, $match->path, $method . ' ' . $uri);
            $this->assertSame($dispatch, $match->method, $method . ' ' . $uri);
        }
    }

    public function testLegacyAliasParametersAreCanonicalizedForAuditTargets(): void
    {
        $order = $this->router->match(
            ['_GET' => ['id' => 'order-001'], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin_shipping_notify_mail?id=order-001'],
        );
        $product = $this->router->match(
            ['_GET' => ['id' => 'sample-001'], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin_product_product_copy?id=sample-001'],
        );
        $member = $this->router->match(
            ['_GET' => ['id' => 'shop-owner'], '_POST' => []],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin_setting_system_member_edit?id=shop-owner'],
        );
        $sort = $this->router->match(
            [
                '_GET' => [],
                '_POST' => [
                    'id' => 'ad000000000000000000000000000002',
                    'sortNo' => '4',
                    '_token' => 'fake-csrf-token-bemart-2026',
                ],
            ],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin_setting_system_member_up'],
        );

        $this->assertSame(['orderNo' => 'order-001'], $order->query);
        $this->assertSame(['productCode' => 'sample-001'], $product->query);
        $this->assertSame(['loginId' => 'shop-owner'], $member->query);
        $this->assertSame('member', $sort->query['masterType']);
        $this->assertSame('ad000000000000000000000000000002', $sort->query['rowId']);
        $this->assertSame('fake-csrf-token-bemart-2026', $sort->query['csrfToken']);
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
