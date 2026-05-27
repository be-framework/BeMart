<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

use Aura\Router\Exception\RouteNotFound as AuraRouteNotFound;
use Aura\Router\Generator;
use Aura\Router\Map;
use Aura\Router\Matcher;
use Aura\Router\Route as AuraRoute;
use Aura\Router\RouterContainer;
use LogicException;

use function array_key_exists;
use function array_keys;
use function array_values;
use function is_array;
use function sprintf;
use function strtolower;
use function strtoupper;

/**
 * Aura.Router route table shared by the HTTP front controller and Twig route helpers.
 *
 * Aura owns the routing mechanics: path matching, placeholder extraction, method
 * matching, and path generation. BeMart keeps only the metadata Aura does not
 * know about in route extras: BEAR resource URI, internal dispatch method, and
 * wire-name-to-resource-name maps.
 *
 * @psalm-type MethodMetadata = array{
 *     resource: string,
 *     dispatchMethod: string,
 *     paramMap: array<string, string>,
 *     defaults: array<string, string>,
 *     queryParamMap: array<string, string>
 * }
 */
final class RouteTable
{
    private const EXTRA_KEY = 'bemart';
    private const METHODS_KEY = 'methods';

    /** @var list<AuraRoute> */
    public readonly array $routes;

    private function __construct(private readonly RouterContainer $container)
    {
        $this->routes = array_values($this->container->getMap()->getRoutes());
    }

    /**
     * The default EC-CUBE-4.3-core Aura route map.
     */
    public static function default(): self
    {
        return self::fromMapBuilder(static function (Map $map): null {
            // ---- Storefront: top + catalogue ----
            self::route($map, 'homepage', ['GET'], '/', 'page://self/');
            self::route($map, 'block_cart', ['GET'], '/block/cart', 'page://self/cart');
            self::route($map, 'product_list', ['GET'], '/products/list', 'page://self/products');
            self::route($map,
                'product_detail',
                ['GET'],
                '/products/detail/{id}',
                'page://self/product',
                ['id' => 'productCode'],
            );

            // ---- Storefront: cart ----
            // EC-CUBE `cart` is GET-only; the BeMart Cart resource serves GET.
            self::route($map, 'cart', ['GET'], '/cart', 'page://self/cart');
            // `product_add_cart` POSTs the add-to-cart form. EC-CUBE keys it
            // by the product id in the path; the Cart/Item resource's onPost
            // takes `$productCode`, so the path id renames to `productCode`.
            self::route($map,
                'product_add_cart',
                ['POST'],
                '/products/add_cart/{id}',
                'page://self/cart/item',
                ['id' => 'productCode'],
            );
            // `cart_handle_item` is the BeMart port's own helper name for the
            // quantity up/down/remove controls in Cart.html.twig (EC-CUBE 4.3
            // splits these across cart_up/cart_down/cart_remove). HTML exposes
            // GET/POST only: GET falls back to the cart page, POST calls the
            // Cart/Item resource.
            self::route($map, 'cart_handle_item', ['GET'], '/cart/item', 'page://self/cart');
            self::route($map, 'cart_handle_item', ['POST'], '/cart/item', 'page://self/cart/item');

            // ---- Storefront: contact ----
            // `contact` serves the form (GET) and the doSubmitContact
            // POST. BeMart's Contact resource collapses EC-CUBE's
            // confirm/complete `mode` branching into a single onPost
            // (see Contact::onPost) — the form posts straight here and
            // the resource redirects to `/contact/complete` on success.
            self::route($map, 'contact', ['GET', 'POST'], '/contact', 'page://self/contact');
            self::route($map, 'contact_confirm', ['POST'], '/contact/confirm', 'page://self/contact/confirm', [], 'get');
            self::route($map, 'contact_complete', ['GET'], '/contact/complete', 'page://self/contact/complete');

            // ---- Storefront: customer registration ----
            self::route($map, 'entry', ['GET', 'POST'], '/entry', 'page://self/entry');
            self::route($map, 'entry_confirm', ['GET'], '/entry/confirm', 'page://self/entry/confirm');
            self::route($map, 'entry_complete', ['GET'], '/entry/complete', 'page://self/entry/complete');
            self::route($map,
                'entry_activate',
                ['GET', 'POST'],
                '/entry/activate/{secret_key}',
                'page://self/entry/activate',
                ['secret_key' => 'secretKey'],
            );

            // ---- Storefront: authentication ----
            self::route($map, 'mypage_login', ['GET', 'POST'], '/mypage/login', 'page://self/login');
            self::route($map, 'logout', ['POST'], '/logout', 'page://self/logout');

            // ---- Storefront: password reset ----
            self::route($map, 'forgot', ['GET', 'POST'], '/forgot', 'page://self/forgot-password');
            self::route($map, 'forgot_complete', ['GET'], '/forgot/complete', 'page://self/forgot-complete');
            self::route($map,
                'forgot_reset',
                ['GET', 'POST'],
                '/forgot/reset/{reset_key}',
                'page://self/reset',
                ['reset_key' => 'resetKey'],
            );

            // ---- Storefront: mypage ----
            self::route($map, 'mypage', ['GET'], '/mypage', 'page://self/mypage');
            self::route($map, 'mypage_change', ['GET', 'POST'], '/mypage/change', 'page://self/mypage/change');
            self::route($map,
                'mypage_change_complete',
                ['GET'],
                '/mypage/change_complete',
                'page://self/mypage/change-complete',
            );
            self::route($map, 'mypage_delivery', ['GET'], '/mypage/delivery', 'page://self/mypage/address-list');
            self::route($map, 'mypage_delivery_new', ['GET'], '/mypage/delivery/new', 'page://self/mypage/address');
            self::route($map, 'mypage_delivery_new', ['POST'], '/mypage/delivery/new', 'page://self/mypage/address-list');
            self::route($map,
                'mypage_delivery_edit',
                ['GET'],
                '/mypage/delivery/{id}/edit',
                'page://self/mypage/address',
                ['id' => 'addressId'],
            );
            self::route($map,
                'mypage_delivery_edit',
                ['POST'],
                '/mypage/delivery/{id}/edit',
                'page://self/mypage/address',
                ['id' => 'addressId'],
                'put',
            );
            self::route($map, 'mypage_delivery_delete', ['GET'], '/mypage/delivery/delete', 'page://self/action-redirect', [], null, ['returnTo' => '/mypage/delivery']);
            self::route($map, 'mypage_delivery_delete', ['POST'], '/mypage/delivery/delete', 'page://self/mypage/address', [], 'delete', [], ['id' => 'addressId']);
            self::route($map, 'mypage_favorite', ['GET'], '/mypage/favorite', 'page://self/mypage/favorite-list');
            self::route($map, 'mypage_favorite_delete', ['GET'], '/mypage/favorite/delete', 'page://self/action-redirect', [], null, ['returnTo' => '/mypage/favorite']);
            self::route($map, 'mypage_favorite_delete', ['POST'], '/mypage/favorite/delete', 'page://self/mypage/favorite', [], 'delete', [], ['id' => 'productCode']);
            self::route($map,
                'mypage_history',
                ['GET'],
                '/mypage/history/{order_no}',
                'page://self/mypage/history',
                ['order_no' => 'orderNo'],
            );
            self::route($map, 'mypage_withdraw', ['GET', 'POST'], '/mypage/withdraw', 'page://self/mypage/withdraw');
            self::route($map,
                'mypage_withdraw_complete',
                ['GET'],
                '/mypage/withdraw_complete',
                'page://self/mypage/withdraw-complete',
            );

            // ---- Storefront: shopping (checkout flow) ----
            self::route($map, 'shopping', ['GET'], '/shopping', 'page://self/shopping');
            self::route($map, 'shopping_shipping', ['GET'], '/shopping/shipping', 'page://self/shopping/shipping');
            self::route($map, 'shopping_shipping', ['POST'], '/shopping/shipping', 'page://self/action-redirect', [], null, ['returnTo' => '/shopping']);
            self::route($map, 'shopping_shipping_edit', ['GET'], '/shopping/shipping/edit', 'page://self/shopping/shipping-edit');
            self::route($map, 'shopping_shipping_edit', ['POST'], '/shopping/shipping/edit', 'page://self/action-redirect', [], null, ['returnTo' => '/shopping']);
            self::route($map, 'shopping_shipping_multiple', ['GET'], '/shopping/shipping/multiple', 'page://self/shopping/shipping-multiple');
            self::route($map, 'shopping_shipping_multiple', ['POST'], '/shopping/shipping/multiple', 'page://self/action-redirect', [], null, ['returnTo' => '/shopping']);
            self::route($map, 'shopping_shipping_multiple_edit', ['GET'], '/shopping/shipping/multiple/edit', 'page://self/shopping/shipping-multiple-edit');
            self::route($map, 'shopping_shipping_multiple_edit', ['POST'], '/shopping/shipping/multiple/edit', 'page://self/action-redirect', [], null, ['returnTo' => '/shopping']);
            self::route($map, 'shopping_login', ['GET'], '/shopping/login', 'page://self/shopping/login');
            self::route($map, 'shopping_nonmember', ['GET', 'POST'], '/shopping/nonmember', 'page://self/shopping/non-member');
            self::route($map, 'shopping_confirm', ['POST'], '/shopping/confirm', 'page://self/shopping/confirm', [], 'get');
            self::route($map, 'shopping_checkout', ['POST'], '/shopping/checkout', 'page://self/shopping/checkout');
            self::route($map, 'shopping_complete', ['GET'], '/shopping/complete', 'page://self/shopping/complete');
            self::route($map, 'shopping_error', ['GET'], '/shopping/error', 'page://self/shopping/error');

            // ---- Storefront: static help pages ----
            self::route($map, 'help_about', ['GET'], '/help/about', 'page://self/help/about');
            self::route($map, 'help_guide', ['GET'], '/guide', 'page://self/help/guide');
            self::route($map, 'help_agreement', ['GET'], '/help/agreement', 'page://self/help/agreement');
            self::route($map, 'help_privacy', ['GET'], '/help/privacy', 'page://self/help/privacy');
            self::route($map, 'help_tradelaw', ['GET'], '/help/tradelaw', 'page://self/help/trade-law');

            // ---- Admin: dashboard + auth ----
            self::route($map, 'admin_login', ['GET', 'POST'], '/admin/login', 'page://self/admin/login');
            // The dashboard resource is `Resource\Page\Admin\Index`; its
            // BEAR URI is `page://self/admin/index` (a bare
            // `page://self/admin` resolves to a non-existent `Page\Admin`
            // class — Unbound). `/admin` is the EC-CUBE `admin_homepage`
            // path.
            self::route($map, 'admin_homepage', ['GET'], '/admin', 'page://self/admin/index');
            self::route($map, 'admin_logout', ['POST'], '/admin/logout', 'page://self/admin/logout');
            self::route($map,
                'admin_change_password',
                ['GET'],
                '/admin/change_password',
                'page://self/admin/change-password',
            );
            self::route($map,
                'admin_change_password',
                ['POST'],
                '/admin/change_password',
                'page://self/admin/action-redirect',
                [],
                null,
                ['returnTo' => '/admin/change_password'],
            );

            // ---- Admin: catalogue ----
            self::route($map, 'admin_product', ['GET', 'POST'], '/admin/product', 'page://self/admin/product-list', [], 'get');
            self::route($map, 'admin_product_tag', ['GET', 'POST'], '/admin/product/tag', 'page://self/admin/tag/tag-list', [], 'get');
            self::route($map,
                'admin_product_class_name',
                ['GET', 'POST'],
                '/admin/product/class_name',
                'page://self/admin/class-name/class-name-list',
                [],
                'get',
            );
            self::route($map,
                'admin_product_category',
                ['GET', 'POST'],
                '/admin/product/category',
                'page://self/admin/category/category-list',
                [],
                'get',
            );

            // ---- Admin: orders + customers ----
            self::route($map, 'admin_order', ['GET', 'POST'], '/admin/order', 'page://self/admin/order-list', [], 'get');
            self::route($map, 'admin_customer', ['GET', 'POST'], '/admin/customer', 'page://self/admin/customer-list', [], 'get');
            // `admin_customer_resend` POSTs the "resend the email-verification
            // mail to a 仮会員" action from a customer-list row. EC-CUBE keys
            // its route by the customer id in the path; the BeMart Be Input
            // takes the customer's `email`, so the action POSTs the email in
            // the body and the path stays parameterless.
            self::route($map,
                'admin_customer_resend',
                ['POST'],
                '/admin/customer/resend-activation-mail',
                'page://self/admin/customer/resend-activation-mail',
            );

            // ---- Admin: content (CMS) ----
            self::route($map,
                'admin_content_news',
                ['GET'],
                '/admin/content/news',
                'page://self/admin/news/news-list',
            );
            self::route($map,
                'admin_content_page',
                ['GET'],
                '/admin/content/page',
                'page://self/admin/page/page-list',
            );
            self::route($map,
                'admin_content_layout',
                ['GET'],
                '/admin/content/layout',
                'page://self/admin/layout/layout-list',
            );
            self::route($map,
                'admin_content_block',
                ['GET'],
                '/admin/content/block',
                'page://self/admin/block/block-list',
            );

            // ---- Admin: shop + system settings ----
            self::route($map,
                'admin_setting_shop_payment',
                ['GET'],
                '/admin/setting/shop/payment',
                'page://self/admin/payment/payment-list',
            );
            self::route($map,
                'admin_setting_shop_delivery',
                ['GET'],
                '/admin/setting/shop/delivery',
                'page://self/admin/delivery/delivery-list',
            );
            self::route($map,
                'admin_setting_shop_tax',
                ['GET', 'POST'],
                '/admin/setting/shop/tax',
                'page://self/admin/tax-rule/tax-rule-list',
                [],
                'get',
            );
            self::route($map,
                'admin_setting_system_member',
                ['GET', 'POST'],
                '/admin/setting/system/member',
                'page://self/admin/member-list',
                [],
                'get',
            );
            self::adminAliasRoutes($map);


            return null;
        });
    }

    /** @param callable(Map): null $builder */
    public static function fromMapBuilder(callable $builder): self
    {
        $container = new RouterContainer();
        $container->setMapBuilder($builder);

        return new self($container);
    }

    public function matcher(): Matcher
    {
        return $this->container->getMatcher();
    }

    public function generator(): Generator
    {
        return $this->container->getGenerator();
    }

    public function map(): Map
    {
        return $this->container->getMap();
    }

    /** Look an Aura route up by its EC-CUBE name, or null when absent. */
    public function byName(string $name): AuraRoute|null
    {
        try {
            return $this->map()->getRoute($name);
        } catch (AuraRouteNotFound) {
            return null;
        }
    }

    /** @return MethodMetadata */
    public static function metadataFor(AuraRoute $route, string $method): array
    {
        $methods = self::methodMetadataFor($route);
        $method = strtoupper($method);
        if (! array_key_exists($method, $methods)) {
            throw new LogicException(sprintf('Aura route "%s" has no BeMart metadata for %s.', (string) $route->name, $method));
        }

        return $methods[$method];
    }

    /** @return array<string, MethodMetadata> */
    public static function methodMetadataFor(AuraRoute $route): array
    {
        /** @var mixed $bemart */
        $bemart = $route->extras[self::EXTRA_KEY] ?? null;
        if (! is_array($bemart) || ! isset($bemart[self::METHODS_KEY]) || ! is_array($bemart[self::METHODS_KEY])) {
            return [];
        }

        /** @var array<string, MethodMetadata> */
        return $bemart[self::METHODS_KEY];
    }

    /**
     * @param list<string>          $methods
     * @param array<string,string> $paramMap
     * @param array<string,string> $defaults
     * @param array<string,string> $queryParamMap
     */
    public static function addRoute(
        Map $map,
        string $name,
        array $methods,
        string $path,
        string $resource,
        array $paramMap = [],
        string|null $dispatchMethod = null,
        array $defaults = [],
        array $queryParamMap = [],
    ): void {
        self::route($map, $name, $methods, $path, $resource, $paramMap, $dispatchMethod, $defaults, $queryParamMap);
    }

    /**
     * @param list<string>          $methods
     * @param array<string,string> $paramMap
     * @param array<string,string> $defaults
     * @param array<string,string> $queryParamMap
     */
    private static function route(
        Map $map,
        string $name,
        array $methods,
        string $path,
        string $resource,
        array $paramMap = [],
        string|null $dispatchMethod = null,
        array $defaults = [],
        array $queryParamMap = [],
    ): void {
        $route = self::auraRoute($map, $name, $path);
        $existingMethods = self::methodMetadataFor($route);
        $newMethods = [];
        foreach ($methods as $method) {
            $method = strtoupper($method);
            if (array_key_exists($method, $existingMethods) || array_key_exists($method, $newMethods)) {
                throw new LogicException(sprintf('Route "%s" defines %s more than once.', $name, $method));
            }

            $newMethods[$method] = [
                'resource' => $resource,
                'dispatchMethod' => strtolower($dispatchMethod ?? $method),
                'paramMap' => $paramMap,
                'defaults' => $defaults,
                'queryParamMap' => $queryParamMap,
            ];
        }

        $route->allows(array_keys($newMethods));
        $route->extras([
            self::EXTRA_KEY => [
                self::METHODS_KEY => $newMethods,
            ],
        ]);
    }

    private static function auraRoute(Map $map, string $name, string $path): AuraRoute
    {
        try {
            $route = $map->getRoute($name);
        } catch (AuraRouteNotFound) {
            return $map->route($name, $path, $name);
        }

        if ($route->path !== $path) {
            throw new LogicException(sprintf(
                'Route "%s" cannot be represented as one Aura route because it has multiple paths: %s and %s.',
                $name,
                (string) $route->path,
                $path,
            ));
        }

        return $route;
    }

    private static function adminAliasRoutes(Map $map): void
    {
            // Content / CMS.
            self::adminGet($map, 'admin_content_block_new', 'page://self/admin/block/block');
            self::adminGet($map, 'admin_content_block_edit', 'page://self/admin/block/block', ['id' => 'blockId']);
            self::adminGet($map, 'admin_content_block_delete', 'page://self/admin/block/block-list');
            self::adminPost($map, 'admin_content_block_delete', 'page://self/admin/block/block', 'delete', ['id' => 'blockId']);
            self::adminGet($map, 'admin_content_cache', 'page://self/admin/content/cache');
            self::adminPost($map, 'admin_content_cache', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_content_cache']);
            self::adminGet($map, 'admin_content_css', 'page://self/admin/content/css');
            self::adminPost($map, 'admin_content_css', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_content_css']);
            self::adminGet($map, 'admin_content_js', 'page://self/admin/content/js');
            self::adminPost($map, 'admin_content_js', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_content_js']);
            self::adminGet($map, 'admin_content_layout_new', 'page://self/admin/layout/layout');
            self::adminGet($map, 'admin_content_layout_edit', 'page://self/admin/layout/layout', ['id' => 'layoutId']);
            self::adminGet($map, 'admin_content_maintenance', 'page://self/admin/content/maintenance');
            self::adminPost($map, 'admin_content_maintenance', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_content_maintenance']);
            self::adminGet($map, 'admin_content_news_new', 'page://self/admin/news/news');
            self::adminGet($map, 'admin_content_news_edit', 'page://self/admin/news/news', ['id' => 'newsId']);
            self::adminGet($map, 'admin_content_news_delete', 'page://self/admin/news/news-list');
            self::adminPost($map, 'admin_content_news_delete', 'page://self/admin/news/news', 'delete', ['id' => 'newsId']);
            self::adminGet($map, 'admin_content_page_new', 'page://self/admin/page/page');
            self::adminGet($map, 'admin_content_page_edit', 'page://self/admin/page/page', ['id' => 'pageId']);
            self::adminGet($map, 'admin_content_page_delete', 'page://self/admin/page/page-list');
            self::adminPost($map, 'admin_content_page_delete', 'page://self/admin/page/page', 'delete', ['id' => 'pageId']);

            // Customer.
            self::adminGet($map, 'admin_customer_edit', 'page://self/admin/customer', ['id' => 'customerId']);
            self::adminGet($map, 'admin_customer_delete', 'page://self/admin/customer-list');
            self::adminPost($map, 'admin_customer_delete', 'page://self/admin/delete-customer', 'post', ['id' => 'customerId']);
            self::adminGet($map, 'admin_customer_export', 'page://self/admin/customer-csv');
            self::adminPost($map, 'admin_customer_export', 'page://self/admin/customer-csv', 'get');
            self::adminGet($map, 'admin_customer_delivery_new', 'page://self/admin/customer-delivery-edit', ['id' => 'customerId']);

            // Dashboard drill-downs.
            self::adminGet($map, 'admin_homepage_customer', 'page://self/admin/customer-list');
            self::adminGet($map, 'admin_homepage_nonstock', 'page://self/admin/product-list');
            self::adminGet($map, 'admin_homepage_sale', 'page://self/admin/order-list');

            // Order.
            self::adminGet($map, 'admin_order_edit', 'page://self/admin/order/edit', ['id' => 'orderNo']);
            self::adminGet($map, 'admin_order_bulk_delete', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/order']);
            self::adminPost($map, 'admin_order_bulk_delete', 'page://self/admin/order/bulk-delete', 'post', ['ids' => 'orderNos']);
            self::adminGet($map, 'admin_order_csv_shipping', 'page://self/admin/order/import-shipping');
            self::adminPost($map, 'admin_order_csv_shipping', 'page://self/admin/order/import-shipping', 'post', ['import_file' => 'csv'], ['csv' => '']);
            self::adminGet($map, 'admin_order_export_order', 'page://self/admin/order/export-order');
            self::adminPost($map, 'admin_order_export_order', 'page://self/admin/order/export-order', 'get');
            self::adminGet($map, 'admin_order_export_pdf', 'page://self/admin/order/export-order-pdf', ['ids' => 'orderNo']);
            self::adminPost($map, 'admin_order_export_pdf', 'page://self/admin/order/export-order-pdf', 'get', ['ids' => 'orderNo']);
            self::adminGet($map, 'admin_order_export_shipping', 'page://self/admin/order/export-shipping');
            self::adminPost($map, 'admin_order_export_shipping', 'page://self/admin/order/export-shipping', 'get');
            self::adminGetPost($map, 'admin_order_mail', 'page://self/admin/order/send-mail', ['id' => 'orderNo']);
            self::adminGetPost($map, 'admin_order_shipping', 'page://self/admin/order/shipping-address', ['id' => 'orderNo']);
            self::adminGet($map, 'admin_shipping_notify_mail', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/order']);
            self::adminPost($map, 'admin_shipping_notify_mail', 'page://self/admin/order/shipping-notify-mail', 'post', ['id' => 'orderNo']);
            self::adminGet($map, 'admin_shipping_preview_notify_mail', 'page://self/admin/order/mail-confirm', ['id' => 'orderNo']);
            self::adminPost($map, 'admin_shipping_preview_notify_mail', 'page://self/admin/order/mail-confirm', 'get', ['id' => 'orderNo']);
            self::adminGet($map, 'admin_shipping_update_order_status', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/order']);
            self::adminPost($map, 'admin_shipping_update_order_status', 'page://self/admin/order', 'put', ['id' => 'orderNo']);
            self::adminGet($map, 'admin_shipping_update_tracking_number', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/order']);
            self::adminPost($map, 'admin_shipping_update_tracking_number', 'page://self/admin/order/tracking-number', 'put', ['id' => 'orderNo']);

            // Product / catalogue.
            self::adminGet($map, 'admin_product_product_new', 'page://self/admin/product-new');
            self::adminPost($map, 'admin_product_product_new', 'page://self/admin/product');
            self::adminGet($map, 'admin_product_product_edit', 'page://self/admin/product/edit', ['id' => 'productCode']);
            self::adminPost($map, 'admin_product_product_edit', 'page://self/admin/product', 'put', ['id' => 'productCode']);
            self::adminGet($map, 'admin_product_product_delete', 'page://self/admin/product-list');
            self::adminPost($map, 'admin_product_product_delete', 'page://self/admin/product', 'delete', ['id' => 'productCode']);
            self::adminGet($map, 'admin_product_product_copy', 'page://self/admin/action-redirect', ['id' => 'productCode'], ['returnTo' => '/admin/product']);
            self::adminPost($map, 'admin_product_product_copy', 'page://self/admin/product-copy', 'post', ['id' => 'productCode']);
            self::adminGet($map, 'admin_product_product_class', 'page://self/admin/product/product-class', ['id' => 'productCode']);
            self::adminPost($map, 'admin_product_product_class', 'page://self/admin/product/product-class', 'get', ['id' => 'productCode']);
            self::adminGet($map, 'admin_product_bulk_product_status', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product']);
            self::adminPost($map, 'admin_product_bulk_product_status', 'page://self/admin/product-bulk-status');
            self::adminGet($map, 'admin_product_export', 'page://self/admin/product-csv');
            self::adminPost($map, 'admin_product_export', 'page://self/admin/product-csv', 'get');
            self::adminGet($map, 'admin_product_csv_product', 'page://self/admin/product-csv');
            self::adminPost($map, 'admin_product_csv_product', 'page://self/admin/product-csv', 'get');
            self::adminGet($map, 'admin_product_csv_category', 'page://self/admin/category/csv');
            self::adminPost($map, 'admin_product_csv_category', 'page://self/admin/category/csv', 'post', ['import_file' => 'csv'], ['csv' => '']);
            self::adminGetPost($map, 'admin_product_csv_class_name', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']);
            self::adminGetPost($map, 'admin_product_csv_class_category', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']);
            self::adminGet($map, 'admin_product_category_edit', 'page://self/admin/category/category', ['id' => 'categoryId']);
            self::adminGetPost($map, 'admin_product_class_category', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId']);
            self::adminGet($map, 'admin_product_class_category_edit', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId', 'id' => 'classCategoryId']);
            self::adminGet($map, 'admin_product_class_category_delete', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId']);
            self::adminPost($map, 'admin_product_class_category_delete', 'page://self/admin/class-category/class-category', 'delete', ['id' => 'classCategoryId']);
            self::adminGetPost($map, 'admin_product_class_category_export', 'page://self/admin/action-redirect', ['class_name_id' => 'classNameId'], ['returnTo' => '/admin/product/class_name']);
            self::adminGet($map, 'admin_product_class_category_sort_no_move', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']);
            self::adminPost($map, 'admin_product_class_category_sort_no_move', 'page://self/admin/sort-no-move', 'put', [], ['masterType' => 'class_category']);
            self::adminGet($map, 'admin_product_class_category_visibility', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']);
            self::adminPost($map, 'admin_product_class_category_visibility', 'page://self/admin/toggle-visible', 'put', ['id' => 'rowId'], ['masterType' => 'class_category']);
            self::adminGet($map, 'admin_product_class_name_delete', 'page://self/admin/class-name/class-name-list');
            self::adminPost($map, 'admin_product_class_name_delete', 'page://self/admin/class-name/class-name', 'delete', ['id' => 'classNameId']);
            self::adminGetPost($map, 'admin_product_class_name_export', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']);
            self::adminGet($map, 'admin_product_class_name_sort_no_move', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']);
            self::adminPost($map, 'admin_product_class_name_sort_no_move', 'page://self/admin/sort-no-move', 'put', [], ['masterType' => 'class_name']);
            self::adminGet($map, 'admin_product_tag_delete', 'page://self/admin/tag/tag-list');
            self::adminPost($map, 'admin_product_tag_delete', 'page://self/admin/tag/tag', 'delete', ['id' => 'tagId']);
            self::adminGet($map, 'admin_product_tag_sort_no_move', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/tag']);
            self::adminPost($map, 'admin_product_tag_sort_no_move', 'page://self/admin/sort-no-move', 'put', [], ['masterType' => 'tag']);

            // Shop settings.
            self::adminGetPost($map, 'admin_setting_shop', 'page://self/admin/base-info');
            self::adminGet($map, 'admin_setting_shop_calendar', 'page://self/admin/calendar');
            self::adminPost($map, 'admin_setting_shop_calendar', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_shop_calendar']);
            self::adminGet($map, 'admin_setting_shop_calendar_new', 'page://self/admin/calendar');
            self::adminPost($map, 'admin_setting_shop_calendar_new', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_shop_calendar']);
            self::adminGetPost($map, 'admin_setting_shop_calendar_delete', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin_setting_shop_calendar']);
            self::adminGetPost($map, 'admin_setting_shop_csv', 'page://self/admin/csv-config');
            self::adminGet($map, 'admin_setting_shop_delivery_new', 'page://self/admin/delivery/delivery');
            self::adminGet($map, 'admin_setting_shop_delivery_edit', 'page://self/admin/delivery/delivery', ['id' => 'deliveryId']);
            self::adminGet($map, 'admin_setting_shop_delivery_delete', 'page://self/admin/delivery/delivery-list');
            self::adminPost($map, 'admin_setting_shop_delivery_delete', 'page://self/admin/delivery/delivery', 'delete', ['id' => 'deliveryId']);
            self::adminGet($map, 'admin_setting_shop_delivery_sort_no_move', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/setting/shop/delivery']);
            self::adminPost($map, 'admin_setting_shop_delivery_sort_no_move', 'page://self/admin/sort-no-move', 'put', [], ['masterType' => 'delivery']);
            self::adminGet($map, 'admin_setting_shop_delivery_visibility', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/setting/shop/delivery']);
            self::adminPost($map, 'admin_setting_shop_delivery_visibility', 'page://self/admin/toggle-visible', 'put', ['id' => 'rowId'], ['masterType' => 'delivery']);
            self::adminGetPost($map, 'admin_setting_shop_mail', 'page://self/admin/mail-template');
            self::adminGetPost($map, 'admin_setting_shop_mail_delete', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin_setting_shop_mail']);
            self::adminGet($map, 'admin_setting_shop_order_status', 'page://self/admin/order-status');
            self::adminPost($map, 'admin_setting_shop_order_status', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_shop_order_status']);
            self::adminGet($map, 'admin_setting_shop_payment_new', 'page://self/admin/payment/payment');
            self::adminGet($map, 'admin_setting_shop_payment_edit', 'page://self/admin/payment/payment', ['id' => 'paymentId']);
            self::adminGet($map, 'admin_setting_shop_payment_delete', 'page://self/admin/payment/payment-list');
            self::adminPost($map, 'admin_setting_shop_payment_delete', 'page://self/admin/payment/payment', 'delete', ['id' => 'paymentId']);
            self::adminGet($map, 'admin_setting_shop_payment_sort_no_move', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/setting/shop/payment']);
            self::adminPost($map, 'admin_setting_shop_payment_sort_no_move', 'page://self/admin/sort-no-move', 'put', [], ['masterType' => 'payment']);
            self::adminGet($map, 'admin_setting_shop_payment_visible', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/setting/shop/payment']);
            self::adminPost($map, 'admin_setting_shop_payment_visible', 'page://self/admin/toggle-visible', 'put', ['id' => 'rowId'], ['masterType' => 'payment']);
            self::adminPost($map, 'admin_setting_shop_tax_new', 'page://self/admin/tax-rule/tax-rule-list', 'post', ['tax_rate' => 'taxRate', 'apply_date' => 'applyDate', 'rounding_type' => 'roundingType']);
            self::adminGet($map, 'admin_setting_shop_tax_delete', 'page://self/admin/tax-rule/tax-rule-list');
            self::adminPost($map, 'admin_setting_shop_tax_delete', 'page://self/admin/tax-rule/tax-rule', 'delete', ['id' => 'taxRuleId']);
            self::adminGet($map, 'admin_setting_shop_tradelaw', 'page://self/admin/trade-law');
            self::adminPost($map, 'admin_setting_shop_tradelaw', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_shop_tradelaw']);

            // System settings.
            self::adminGetPost($map, 'admin_setting_system_authority', 'page://self/admin/authority-role');
            self::adminGet($map, 'admin_setting_system_masterdata', 'page://self/admin/master-data');
            self::adminPost($map, 'admin_setting_system_masterdata', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_system_masterdata']);
            self::adminGet($map, 'admin_setting_system_masterdata_edit', 'page://self/admin/master-data');
            self::adminPost($map, 'admin_setting_system_masterdata_edit', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_system_masterdata']);
            self::adminGet($map, 'admin_setting_system_member_new', 'page://self/admin/member');
            self::adminPost($map, 'admin_setting_system_member_new', 'page://self/admin/member');
            self::adminGet($map, 'admin_setting_system_member_edit', 'page://self/admin/member', ['id' => 'loginId']);
            self::adminPost($map, 'admin_setting_system_member_edit', 'page://self/admin/member', 'put', ['id' => 'loginId']);
            self::adminGet($map, 'admin_setting_system_member_delete', 'page://self/admin/member-list');
            self::adminPost($map, 'admin_setting_system_member_delete', 'page://self/admin/member', 'delete', ['id' => 'loginId']);
            self::adminGetPost($map, 'admin_setting_system_member_up', 'page://self/admin/action-redirect', ['id' => 'loginId'], ['returnTo' => '/admin/setting/system/member']);
            self::adminGetPost($map, 'admin_setting_system_member_down', 'page://self/admin/action-redirect', ['id' => 'loginId'], ['returnTo' => '/admin/setting/system/member']);
            self::adminGet($map, 'admin_setting_system_security', 'page://self/admin/security');
            self::adminPost($map, 'admin_setting_system_security', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_system_security']);
            self::adminGet($map, 'admin_setting_system_system_phpinfo', 'page://self/admin/system');
            self::adminPost($map, 'admin_setting_system_system_phpinfo', 'page://self/admin/system', 'get');

            // Store / plugin / template.
            self::adminGet($map, 'admin_store_plugin_owners_search_page', 'page://self/admin/plugin-list');
            self::adminGet($map, 'admin_store_plugin_enable', 'page://self/admin/plugin-list');
            self::adminPost($map, 'admin_store_plugin_enable', 'page://self/admin/plugin-enable', 'post', ['code' => 'pluginCode']);
            self::adminGet($map, 'admin_store_plugin_disable', 'page://self/admin/plugin-list');
            self::adminPost($map, 'admin_store_plugin_disable', 'page://self/admin/plugin-disable', 'post', ['code' => 'pluginCode']);
            self::adminGet($map, 'admin_store_plugin_install', 'page://self/admin/plugin-list');
            self::adminPost($map, 'admin_store_plugin_install', 'page://self/admin/plugin-list', 'post', ['code' => 'pluginCode', 'version' => 'pluginVersion']);
            self::adminGet($map, 'admin_store_plugin_uninstall', 'page://self/admin/plugin-list');
            self::adminPost($map, 'admin_store_plugin_uninstall', 'page://self/admin/plugin', 'delete', ['code' => 'pluginCode']);
            self::adminGet($map, 'admin_store_template', 'page://self/admin/template/template-list');
            self::adminPost($map, 'admin_store_template', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_store_template']);
            self::adminGet($map, 'admin_store_template_install', 'page://self/admin/template/template-add');
            self::adminPost($map, 'admin_store_template_install', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_store_template']);
            self::adminGetPost($map, 'admin_store_template_download', 'page://self/admin/action-redirect', ['id' => 'templateId'], ['returnTo' => '/admin_store_template']);
            self::adminGet($map, 'admin_store_template_delete', 'page://self/admin/template/template-list');
            self::adminPost($map, 'admin_store_template_delete', 'page://self/admin/action-redirect', 'post', ['id' => 'templateId'], ['returnTo' => '/admin_store_template']);
            self::adminGet($map, 'admin_two_factor_auth', 'page://self/admin/two-factor-auth');
            self::adminPost($map, 'admin_two_factor_auth', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_two_factor_auth']);
            self::adminGet($map, 'admin_two_factor_auth_set', 'page://self/admin/two-factor-auth-set');
            self::adminPost($map, 'admin_two_factor_auth_set', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_two_factor_auth']);

    }

    /** @param array<string, string> $queryParamMap */
    private static function adminGet(Map $map, string $name, string $resource, array $queryParamMap = [], array $defaults = []): void
    {
        self::route(
            $map,
            $name,
            ['GET'],
            '/' . $name,
            $resource,
            [],
            null,
            $defaults,
            $queryParamMap,
        );
    }

    /** @param array<string, string> $queryParamMap */
    private static function adminPost(
        Map $map,
        string $name,
        string $resource,
        string|null $dispatchMethod = null,
        array $queryParamMap = [],
        array $defaults = [],
    ): void {
        self::route(
            $map,
            $name,
            ['POST'],
            '/' . $name,
            $resource,
            [],
            $dispatchMethod,
            $defaults,
            $queryParamMap,
        );
    }

    /** @param array<string, string> $queryParamMap */
    private static function adminGetPost(
        Map $map,
        string $name,
        string $resource,
        array $queryParamMap = [],
        array $defaults = [],
    ): void {
        self::route(
            $map,
            $name,
            ['GET', 'POST'],
            '/' . $name,
            $resource,
            [],
            null,
            $defaults,
            $queryParamMap,
        );
    }
}
