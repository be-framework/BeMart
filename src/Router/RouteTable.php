<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

/**
 * The route map — single source of truth shared by the HTTP front
 * controller ({@see Router}) and the `url()` / `path()` Twig helpers
 * ({@see \MyVendor\BeMart\Module\BeMartTwigExtension}).
 *
 * ## Why one table, two consumers
 *
 * The ported EC-CUBE templates link via `url('route_name', params)` — an
 * EC-CUBE route NAME, not a path. An HTTP request, conversely, arrives as
 * a PATH. If the helper and the router each carried their own idea of the
 * mapping they would drift; a template would emit an href the router
 * cannot resolve. So both pull from this one table: `url('product_detail',
 * {id: 5})` produces `/products/detail/5`, and a GET of `/products/detail/5`
 * resolves back to `page://self/product` — guaranteed agreement.
 *
 * ## Three vocabularies reconciled
 *
 * Each {@see Route} reconciles three names that do NOT coincide in this
 * port:
 *
 *  1. EC-CUBE route NAME    — `product_detail`     (what templates link to)
 *  2. EC-CUBE URL PATH      — `/products/detail/{id}`
 *  3. BEAR resource URI     — `page://self/product`
 *
 * and a per-route placeholder map renames EC-CUBE's path-param names to
 * the BEAR resource's `on{Method}` PARAMETER names — EC-CUBE's
 * `product_detail` path param is `id`, but `Product::onGet` declares
 * `$productCode`, so `['id' => 'productCode']` bridges them.
 *
 * ## Scope
 *
 * Routes are limited to EC-CUBE 4.3 core (`tools/ec-cube-source` route
 * annotations) that have a corresponding BeMart BEAR resource — the
 * GET-serving storefront + admin pages plus the state-changing actions
 * the ported templates POST/PUT/DELETE to. Plugin routes are out of scope
 * (standing "plug-in を除く" instruction). Admin paths use EC-CUBE's
 * configurable `%eccube_admin_route%` segment, fixed here to the default
 * `admin`.
 */
final class RouteTable
{
    /** @var list<Route> */
    private array $routes;

    /** @param list<Route> $routes */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * The default EC-CUBE-4.3-core route map.
     *
     * Built once; cheap to construct (plain value objects), so the Twig
     * extension and the front controller each call it without sharing an
     * instance — the data is identical and immutable either way.
     */
    public static function default(): self
    {
        return new self([
            // ---- Storefront: top + catalogue ----
            new Route('homepage', ['GET'], '/', 'page://self/'),
            new Route('product_list', ['GET'], '/products/list', 'page://self/products'),
            new Route(
                'product_detail',
                ['GET'],
                '/products/detail/{id}',
                'page://self/product',
                ['id' => 'productCode'],
            ),

            // ---- Storefront: cart ----
            // EC-CUBE `cart` is GET-only; the BeMart Cart resource serves GET.
            new Route('cart', ['GET'], '/cart', 'page://self/cart'),
            // `product_add_cart` POSTs the add-to-cart form. EC-CUBE keys it
            // by the product id in the path; the Cart/Item resource's onPost
            // takes `$productCode`, so the path id renames to `productCode`.
            new Route(
                'product_add_cart',
                ['POST'],
                '/products/add_cart/{id}',
                'page://self/cart/item',
                ['id' => 'productCode'],
            ),
            // `cart_handle_item` is the BeMart port's own helper name for the
            // quantity up/down/remove anchors in Cart.html.twig (EC-CUBE 4.3
            // splits these across cart_up/cart_down/cart_remove). It maps to
            // the same Cart/Item resource, which serves PUT (quantity) and
            // DELETE (remove); the front controller method drives the verb.
            new Route('cart_handle_item', ['PUT', 'DELETE'], '/cart/item', 'page://self/cart/item'),

            // ---- Storefront: contact ----
            // `contact` serves the form (GET) and the doSubmitContact
            // POST. BeMart's Contact resource collapses EC-CUBE's
            // confirm/complete `mode` branching into a single onPost
            // (see Contact::onPost) — the form posts straight here and
            // the resource redirects to `/contact/complete` on success.
            new Route('contact', ['GET', 'POST'], '/contact', 'page://self/contact'),
            new Route('contact_confirm', ['POST'], '/contact/confirm', 'page://self/contact/confirm'),
            new Route('contact_complete', ['GET'], '/contact/complete', 'page://self/contact/complete'),

            // ---- Storefront: customer registration ----
            new Route('entry', ['GET', 'POST'], '/entry', 'page://self/entry'),
            new Route('entry_confirm', ['GET'], '/entry/confirm', 'page://self/entry/confirm'),
            new Route('entry_complete', ['GET'], '/entry/complete', 'page://self/entry/complete'),
            new Route(
                'entry_activate',
                ['GET', 'POST'],
                '/entry/activate/{secret_key}',
                'page://self/entry/activate',
                ['secret_key' => 'secretKey'],
            ),

            // ---- Storefront: authentication ----
            new Route('mypage_login', ['GET', 'POST'], '/mypage/login', 'page://self/login'),
            new Route('logout', ['POST'], '/logout', 'page://self/logout'),

            // ---- Storefront: password reset ----
            new Route('forgot', ['GET', 'POST'], '/forgot', 'page://self/forgot-password'),
            new Route('forgot_complete', ['GET'], '/forgot/complete', 'page://self/forgot-complete'),
            new Route(
                'forgot_reset',
                ['GET', 'POST'],
                '/forgot/reset/{reset_key}',
                'page://self/reset',
                ['reset_key' => 'resetKey'],
            ),

            // ---- Storefront: mypage ----
            new Route('mypage', ['GET'], '/mypage', 'page://self/mypage'),
            new Route('mypage_change', ['GET', 'POST'], '/mypage/change', 'page://self/mypage/change'),
            new Route(
                'mypage_change_complete',
                ['GET'],
                '/mypage/change_complete',
                'page://self/mypage/change-complete',
            ),
            new Route('mypage_delivery', ['GET'], '/mypage/delivery', 'page://self/mypage/address-list'),
            new Route('mypage_delivery_new', ['GET', 'POST'], '/mypage/delivery/new', 'page://self/mypage/address'),
            new Route(
                'mypage_delivery_edit',
                ['GET', 'POST'],
                '/mypage/delivery/{id}/edit',
                'page://self/mypage/address',
                ['id' => 'addressId'],
            ),
            new Route('mypage_favorite', ['GET'], '/mypage/favorite', 'page://self/mypage/favorite-list'),
            new Route(
                'mypage_history',
                ['GET'],
                '/mypage/history/{order_no}',
                'page://self/mypage/history',
                ['order_no' => 'orderNo'],
            ),
            new Route('mypage_withdraw', ['GET', 'POST'], '/mypage/withdraw', 'page://self/mypage/withdraw'),
            new Route(
                'mypage_withdraw_complete',
                ['GET'],
                '/mypage/withdraw_complete',
                'page://self/mypage/withdraw-complete',
            ),

            // ---- Storefront: shopping (checkout flow) ----
            new Route('shopping', ['GET'], '/shopping', 'page://self/shopping'),
            new Route('shopping_login', ['GET'], '/shopping/login', 'page://self/shopping/login'),
            new Route('shopping_nonmember', ['GET', 'POST'], '/shopping/nonmember', 'page://self/shopping/non-member'),
            new Route('shopping_confirm', ['POST'], '/shopping/confirm', 'page://self/shopping/confirm'),
            new Route('shopping_checkout', ['POST'], '/shopping/checkout', 'page://self/shopping/checkout'),
            new Route('shopping_complete', ['GET'], '/shopping/complete', 'page://self/shopping/complete'),
            new Route('shopping_error', ['GET'], '/shopping/error', 'page://self/shopping/error'),

            // ---- Storefront: static help pages ----
            new Route('help_about', ['GET'], '/help/about', 'page://self/help/about'),
            new Route('help_guide', ['GET'], '/guide', 'page://self/help/guide'),
            new Route('help_agreement', ['GET'], '/help/agreement', 'page://self/help/agreement'),
            new Route('help_privacy', ['GET'], '/help/privacy', 'page://self/help/privacy'),
            new Route('help_tradelaw', ['GET'], '/help/tradelaw', 'page://self/help/trade-law'),

            // ---- Admin: dashboard + auth ----
            new Route('admin_login', ['GET', 'POST'], '/admin/login', 'page://self/admin/login'),
            // The dashboard resource is `Resource\Page\Admin\Index`; its
            // BEAR URI is `page://self/admin/index` (a bare
            // `page://self/admin` resolves to a non-existent `Page\Admin`
            // class — Unbound). `/admin` is the EC-CUBE `admin_homepage`
            // path.
            new Route('admin_homepage', ['GET'], '/admin', 'page://self/admin/index'),
            new Route('admin_logout', ['POST'], '/admin/logout', 'page://self/admin/logout'),
            new Route(
                'admin_change_password',
                ['GET', 'POST'],
                '/admin/change_password',
                'page://self/admin/change-password',
            ),

            // ---- Admin: catalogue ----
            new Route('admin_product', ['GET', 'POST'], '/admin/product', 'page://self/admin/product-list'),
            new Route('admin_product_tag', ['GET', 'POST'], '/admin/product/tag', 'page://self/admin/tag/tag-list'),
            new Route(
                'admin_product_class_name',
                ['GET', 'POST'],
                '/admin/product/class_name',
                'page://self/admin/class-name/class-name-list',
            ),
            new Route(
                'admin_product_category',
                ['GET', 'POST'],
                '/admin/product/category',
                'page://self/admin/category/category-list',
            ),

            // ---- Admin: orders + customers ----
            new Route('admin_order', ['GET', 'POST'], '/admin/order', 'page://self/admin/order-list'),
            new Route('admin_customer', ['GET', 'POST'], '/admin/customer', 'page://self/admin/customer-list'),
            // `admin_customer_resend` POSTs the "resend the email-verification
            // mail to a 仮会員" action from a customer-list row. EC-CUBE keys
            // its route by the customer id in the path; the BeMart Be Input
            // takes the customer's `email`, so the action POSTs the email in
            // the body and the path stays parameterless.
            new Route(
                'admin_customer_resend',
                ['POST'],
                '/admin/customer/resend-activation-mail',
                'page://self/admin/customer/resend-activation-mail',
            ),

            // ---- Admin: content (CMS) ----
            new Route(
                'admin_content_news',
                ['GET'],
                '/admin/content/news',
                'page://self/admin/news/news-list',
            ),
            new Route(
                'admin_content_page',
                ['GET'],
                '/admin/content/page',
                'page://self/admin/page/page-list',
            ),
            new Route(
                'admin_content_layout',
                ['GET'],
                '/admin/content/layout',
                'page://self/admin/layout/layout-list',
            ),
            new Route(
                'admin_content_block',
                ['GET'],
                '/admin/content/block',
                'page://self/admin/block/block-list',
            ),

            // ---- Admin: shop + system settings ----
            new Route(
                'admin_setting_shop_payment',
                ['GET'],
                '/admin/setting/shop/payment',
                'page://self/admin/payment/payment-list',
            ),
            new Route(
                'admin_setting_shop_delivery',
                ['GET'],
                '/admin/setting/shop/delivery',
                'page://self/admin/delivery/delivery-list',
            ),
            new Route(
                'admin_setting_shop_tax',
                ['GET', 'POST'],
                '/admin/setting/shop/tax',
                'page://self/admin/tax-rule/tax-rule-list',
            ),
            new Route(
                'admin_setting_system_member',
                ['GET', 'PUT'],
                '/admin/setting/system/member',
                'page://self/admin/member-list',
            ),
        ]);
    }

    /** @return list<Route> */
    public function routes(): array
    {
        return $this->routes;
    }

    /** Look a route up by its EC-CUBE name, or null when absent. */
    public function byName(string $name): Route|null
    {
        foreach ($this->routes as $route) {
            if ($route->name === $name) {
                return $route;
            }
        }

        return null;
    }
}
