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
 * the ported templates submit with GET/POST. HTML-only routes may still dispatch internally to PUT/DELETE resources. Plugin routes are out of scope
 * (standing "plug-in を除く" instruction). Admin paths use EC-CUBE's
 * configurable `%eccube_admin_route%` segment, fixed here to the default
 * `admin`.
 */
final readonly class RouteTable
{
    /** @param list<Route> $routes */
    public function __construct(
        /** @var list<Route> */
        public array $routes,
    ) {
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
            new Route('block_cart', ['GET'], '/block/cart', 'page://self/cart'),
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
            // quantity up/down/remove controls in Cart.html.twig (EC-CUBE 4.3
            // splits these across cart_up/cart_down/cart_remove). HTML exposes
            // GET/POST only: GET falls back to the cart page, POST calls the
            // Cart/Item resource.
            new Route('cart_handle_item', ['GET'], '/cart/item', 'page://self/cart'),
            new Route('cart_handle_item', ['POST'], '/cart/item', 'page://self/cart/item'),

            // ---- Storefront: contact ----
            // `contact` serves the form (GET) and the doSubmitContact
            // POST. BeMart's Contact resource collapses EC-CUBE's
            // confirm/complete `mode` branching into a single onPost
            // (see Contact::onPost) — the form posts straight here and
            // the resource redirects to `/contact/complete` on success.
            new Route('contact', ['GET', 'POST'], '/contact', 'page://self/contact'),
            new Route('contact_confirm', ['POST'], '/contact/confirm', 'page://self/contact/confirm', [], 'get'),
            new Route('contact_complete', ['GET'], '/contact/complete', 'page://self/contact/complete'),

            // ---- Storefront: customer registration ----
            new Route('entry', ['GET', 'POST'], '/entry', 'page://self/entry'),
            new Route('entry_confirm', ['GET', 'POST'], '/entry/confirm', 'page://self/entry/confirm', [], 'get'),
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
            new Route('mypage_delivery_new', ['GET'], '/mypage/delivery/new', 'page://self/mypage/address'),
            new Route('mypage_delivery_new', ['POST'], '/mypage/delivery/new', 'page://self/mypage/address-list'),
            new Route(
                'mypage_delivery_edit',
                ['GET'],
                '/mypage/delivery/{id}/edit',
                'page://self/mypage/address',
                ['id' => 'addressId'],
            ),
            new Route(
                'mypage_delivery_edit',
                ['POST'],
                '/mypage/delivery/{id}/edit',
                'page://self/mypage/address',
                ['id' => 'addressId'],
                'put',
            ),
            new Route('mypage_delivery_delete', ['GET'], '/mypage/delivery/delete', 'page://self/action-redirect', [], null, ['returnTo' => '/mypage/delivery']),
            new Route('mypage_delivery_delete', ['POST'], '/mypage/delivery/delete', 'page://self/mypage/address', [], 'delete', [], ['id' => 'addressId']),
            new Route('mypage_favorite', ['GET'], '/mypage/favorite', 'page://self/mypage/favorite-list'),
            new Route('mypage_favorite_delete', ['GET'], '/mypage/favorite/delete', 'page://self/action-redirect', [], null, ['returnTo' => '/mypage/favorite']),
            new Route('mypage_favorite_delete', ['POST'], '/mypage/favorite/delete', 'page://self/mypage/favorite', [], 'delete', [], ['id' => 'productCode']),
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
            new Route('shopping_shipping', ['GET'], '/shopping/shipping', 'page://self/shopping/shipping'),
            new Route('shopping_shipping', ['POST'], '/shopping/shipping', 'page://self/action-redirect', [], null, ['returnTo' => '/shopping']),
            new Route('shopping_shipping_edit', ['GET'], '/shopping/shipping/edit', 'page://self/shopping/shipping-edit'),
            new Route('shopping_shipping_edit', ['POST'], '/shopping/shipping/edit', 'page://self/action-redirect', [], null, ['returnTo' => '/shopping']),
            new Route('shopping_shipping_multiple', ['GET'], '/shopping/shipping/multiple', 'page://self/shopping/shipping-multiple'),
            new Route('shopping_shipping_multiple', ['POST'], '/shopping/shipping/multiple', 'page://self/action-redirect', [], null, ['returnTo' => '/shopping']),
            new Route('shopping_shipping_multiple_edit', ['GET'], '/shopping/shipping/multiple/edit', 'page://self/shopping/shipping-multiple-edit'),
            new Route('shopping_shipping_multiple_edit', ['POST'], '/shopping/shipping/multiple/edit', 'page://self/action-redirect', [], null, ['returnTo' => '/shopping']),
            new Route('shopping_login', ['GET'], '/shopping/login', 'page://self/shopping/login'),
            new Route('shopping_nonmember', ['GET', 'POST'], '/shopping/nonmember', 'page://self/shopping/non-member'),
            new Route('shopping_confirm', ['POST'], '/shopping/confirm', 'page://self/shopping/confirm', [], 'get'),
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
                ['GET'],
                '/admin/change_password',
                'page://self/admin/change-password',
            ),
            new Route(
                'admin_change_password',
                ['POST'],
                '/admin/change_password',
                'page://self/admin/action-redirect',
                [],
                null,
                ['returnTo' => '/admin/change_password'],
            ),

            // ---- Admin: catalogue ----
            new Route('admin_product', ['GET', 'POST'], '/admin/product', 'page://self/admin/product-list', [], 'get'),
            new Route('admin_product_tag', ['GET', 'POST'], '/admin/product/tag', 'page://self/admin/tag/tag-list', [], 'get'),
            new Route(
                'admin_product_class_name',
                ['GET', 'POST'],
                '/admin/product/class_name',
                'page://self/admin/class-name/class-name-list',
                [],
                'get',
            ),
            new Route(
                'admin_product_category',
                ['GET', 'POST'],
                '/admin/product/category',
                'page://self/admin/category/category-list',
                [],
                'get',
            ),

            // ---- Admin: orders + customers ----
            new Route('admin_order', ['GET', 'POST'], '/admin/order', 'page://self/admin/order-list', [], 'get'),
            new Route('admin_customer', ['GET', 'POST'], '/admin/customer', 'page://self/admin/customer-list', [], 'get'),
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
                ['GET', 'POST'],
                '/admin/setting/shop/delivery',
                'page://self/admin/delivery/delivery-list',
                [],
                'get',
            ),
            new Route(
                'admin_setting_shop_tax',
                ['GET', 'POST'],
                '/admin/setting/shop/tax',
                'page://self/admin/tax-rule/tax-rule-list',
                [],
                'get',
            ),
            new Route(
                'admin_setting_system_member',
                ['GET', 'POST'],
                '/admin/setting/system/member',
                'page://self/admin/member-list',
                [],
                'get',
            ),
            ...self::adminAliasRoutes(),
        ]);
    }

    /** @return list<Route> */
    private static function adminAliasRoutes(): array
    {
        return [
            // Content / CMS.
            self::adminGet('admin_content_block_new', 'page://self/admin/block/block'),
            self::adminPost('admin_content_block_new', 'page://self/admin/block/block-list', null, ['name' => 'blockName', 'file_name' => 'blockFileName']),
            self::adminGet('admin_content_block_edit', 'page://self/admin/block/block', ['id' => 'blockId']),
            self::adminPost('admin_content_block_edit', 'page://self/admin/block/block', 'put', ['id' => 'blockId', 'name' => 'blockName', 'file_name' => 'blockFileName']),
            self::adminGet('admin_content_block_delete', 'page://self/admin/block/block-list'),
            self::adminPost('admin_content_block_delete', 'page://self/admin/block/block', 'delete', ['id' => 'blockId']),
            self::adminGet('admin_content_cache', 'page://self/admin/content/cache'),
            self::adminPost('admin_content_cache', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_content_cache']),
            self::adminGet('admin_content_css', 'page://self/admin/content/css'),
            self::adminPost('admin_content_css', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_content_css']),
            self::adminGet('admin_content_js', 'page://self/admin/content/js'),
            self::adminPost('admin_content_js', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_content_js']),
            self::adminGet('admin_content_layout_new', 'page://self/admin/layout/layout'),
            self::adminPost('admin_content_layout_new', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin/content/layout']),
            self::adminGet('admin_content_layout_edit', 'page://self/admin/layout/layout', ['id' => 'layoutId']),
            self::adminPost('admin_content_layout_edit', 'page://self/admin/layout/layout', 'put', ['id' => 'layoutId', 'name' => 'layoutName']),
            self::adminGet('admin_content_maintenance', 'page://self/admin/content/maintenance'),
            self::adminPost('admin_content_maintenance', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_content_maintenance']),
            self::adminGet('admin_content_news_new', 'page://self/admin/news/news'),
            self::adminPost('admin_content_news_new', 'page://self/admin/news/news-list', null, ['title' => 'newsTitle', 'publish_date' => 'publishDate', 'description' => 'newsDescription', 'url' => 'newsUrl', 'link_method' => 'linkMethod']),
            self::adminGet('admin_content_news_edit', 'page://self/admin/news/news', ['id' => 'newsId']),
            self::adminPost('admin_content_news_edit', 'page://self/admin/news/news', 'put', ['id' => 'newsId', 'title' => 'newsTitle', 'publish_date' => 'publishDate', 'description' => 'newsDescription', 'url' => 'newsUrl', 'link_method' => 'linkMethod']),
            self::adminGet('admin_content_news_delete', 'page://self/admin/news/news-list'),
            self::adminPost('admin_content_news_delete', 'page://self/admin/news/news', 'delete', ['id' => 'newsId']),
            self::adminGet('admin_content_page_new', 'page://self/admin/page/page'),
            self::adminPost('admin_content_page_new', 'page://self/admin/page/page-list', null, ['name' => 'pageName', 'url' => 'pageUrl', 'file_name' => 'pageFileName']),
            self::adminGet('admin_content_page_edit', 'page://self/admin/page/page', ['id' => 'pageId']),
            self::adminPost('admin_content_page_edit', 'page://self/admin/page/page', 'put', ['id' => 'pageId', 'name' => 'pageName', 'url' => 'pageUrl', 'file_name' => 'pageFileName']),
            self::adminGet('admin_content_page_delete', 'page://self/admin/page/page-list'),
            self::adminPost('admin_content_page_delete', 'page://self/admin/page/page', 'delete', ['id' => 'pageId']),

            // Customer.
            self::adminGet('admin_customer_edit', 'page://self/admin/customer', ['id' => 'customerId']),
            self::adminPost('admin_customer_edit', 'page://self/admin/action-redirect', 'post', ['id' => 'customerId'], ['returnTo' => '/admin/customer']),
            self::adminGet('admin_customer_delete', 'page://self/admin/customer-list'),
            self::adminPost('admin_customer_delete', 'page://self/admin/delete-customer', 'post', ['id' => 'customerId']),
            self::adminGet('admin_customer_export', 'page://self/admin/customer-csv'),
            self::adminPost('admin_customer_export', 'page://self/admin/customer-csv', 'get'),
            self::adminGet('admin_customer_delivery_new', 'page://self/admin/customer-delivery-edit', ['id' => 'customerId']),
            self::adminPost('admin_customer_delivery_new', 'page://self/admin/action-redirect', 'post', ['id' => 'customerId'], ['returnTo' => '/admin/customer']),

            // Dashboard drill-downs.
            self::adminGet('admin_homepage_customer', 'page://self/admin/customer-list'),
            self::adminPost('admin_homepage_customer', 'page://self/admin/customer-list', 'get'),
            self::adminGet('admin_homepage_nonstock', 'page://self/admin/product-list'),
            self::adminPost('admin_homepage_nonstock', 'page://self/admin/product-list', 'get'),
            self::adminGet('admin_homepage_sale', 'page://self/admin/order-list'),
            self::adminPost('admin_homepage_sale', 'page://self/admin/order-list', 'get'),

            // Order.
            self::adminGet('admin_order_edit', 'page://self/admin/order/edit', ['id' => 'orderNo']),
            self::adminPost('admin_order_edit', 'page://self/admin/action-redirect', 'post', ['id' => 'orderNo'], ['returnTo' => '/admin/order']),
            self::adminGet('admin_order_bulk_delete', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/order']),
            self::adminPost('admin_order_bulk_delete', 'page://self/admin/order/bulk-delete', 'post', ['ids' => 'orderNos']),
            self::adminGet('admin_order_csv_shipping', 'page://self/admin/order/import-shipping'),
            self::adminPost('admin_order_csv_shipping', 'page://self/admin/order/import-shipping', 'post', ['import_file' => 'csv'], ['csv' => '']),
            self::adminGet('admin_order_export_order', 'page://self/admin/order/export-order'),
            self::adminPost('admin_order_export_order', 'page://self/admin/order/export-order', 'get'),
            self::adminGet('admin_order_export_pdf', 'page://self/admin/order/export-order-pdf', ['ids' => 'orderNo']),
            self::adminPost('admin_order_export_pdf', 'page://self/admin/order/export-order-pdf', 'get', ['ids' => 'orderNo']),
            self::adminGet('admin_order_export_shipping', 'page://self/admin/order/export-shipping'),
            self::adminPost('admin_order_export_shipping', 'page://self/admin/order/export-shipping', 'get'),
            self::adminGetPost('admin_order_mail', 'page://self/admin/order/send-mail', ['id' => 'orderNo']),
            self::adminGet('admin_order_shipping', 'page://self/admin/order/shipping-address', ['id' => 'orderNo']),
            self::adminPost('admin_order_shipping', 'page://self/admin/order/shipping-address', 'put', ['id' => 'orderNo', 'postal_code' => 'postalCode', 'phone_number' => 'phoneNumber']),
            self::adminGet('admin_shipping_notify_mail', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/order']),
            self::adminPost('admin_shipping_notify_mail', 'page://self/admin/order/shipping-notify-mail', 'post', ['id' => 'orderNo']),
            self::adminGet('admin_shipping_preview_notify_mail', 'page://self/admin/order/mail-confirm', ['id' => 'orderNo']),
            self::adminPost('admin_shipping_preview_notify_mail', 'page://self/admin/order/mail-confirm', 'get', ['id' => 'orderNo']),
            self::adminGet('admin_shipping_update_order_status', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/order']),
            self::adminPost('admin_shipping_update_order_status', 'page://self/admin/order', 'put', ['id' => 'orderNo']),
            self::adminGet('admin_shipping_update_tracking_number', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/order']),
            self::adminPost('admin_shipping_update_tracking_number', 'page://self/admin/order/tracking-number', 'put', ['id' => 'orderNo']),

            // Product / catalogue.
            self::adminGet('admin_product_product_new', 'page://self/admin/product-new'),
            self::adminPost('admin_product_product_new', 'page://self/admin/product'),
            self::adminGet('admin_product_product_edit', 'page://self/admin/product/edit', ['id' => 'productCode']),
            self::adminPost('admin_product_product_edit', 'page://self/admin/product', 'put', ['id' => 'productCode']),
            self::adminGet('admin_product_product_delete', 'page://self/admin/product-list'),
            self::adminPost('admin_product_product_delete', 'page://self/admin/product', 'delete', ['id' => 'productCode']),
            self::adminGet('admin_product_product_copy', 'page://self/admin/action-redirect', ['id' => 'productCode'], ['returnTo' => '/admin/product']),
            self::adminPost('admin_product_product_copy', 'page://self/admin/product-copy', 'post', ['id' => 'productCode']),
            self::adminGet('admin_product_product_class', 'page://self/admin/product/product-class', ['id' => 'productCode']),
            self::adminPost('admin_product_product_class', 'page://self/admin/product/product-class', 'get', ['id' => 'productCode']),
            self::adminGet('admin_product_bulk_product_status', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product']),
            self::adminPost('admin_product_bulk_product_status', 'page://self/admin/product-bulk-status'),
            self::adminGet('admin_product_export', 'page://self/admin/product-csv'),
            self::adminPost('admin_product_export', 'page://self/admin/product-csv', 'get'),
            self::adminGet('admin_product_csv_product', 'page://self/admin/product-csv'),
            self::adminPost('admin_product_csv_product', 'page://self/admin/product-csv', 'get'),
            self::adminGet('admin_product_csv_category', 'page://self/admin/category/csv'),
            self::adminPost('admin_product_csv_category', 'page://self/admin/category/csv', 'post', ['import_file' => 'csv'], ['csv' => '']),
            self::adminGetPost('admin_product_csv_class_name', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']),
            self::adminGetPost('admin_product_csv_class_category', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']),
            self::adminGet('admin_product_category_edit', 'page://self/admin/category/category', ['id' => 'categoryId']),
            self::adminPost('admin_product_category_edit', 'page://self/admin/category/category', 'put', ['id' => 'categoryId']),
            self::adminGetPost('admin_product_class_category', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId', 'name' => 'classCategoryName']),
            self::adminGet('admin_product_class_category_edit', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId', 'id' => 'classCategoryId']),
            self::adminPost('admin_product_class_category_edit', 'page://self/admin/class-category/class-category', 'put', ['class_name_id' => 'classNameId', 'id' => 'classCategoryId', 'name' => 'classCategoryName']),
            self::adminGet('admin_product_class_category_delete', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId']),
            self::adminPost('admin_product_class_category_delete', 'page://self/admin/class-category/class-category', 'delete', ['id' => 'classCategoryId']),
            self::adminGetPost('admin_product_class_category_export', 'page://self/admin/action-redirect', ['class_name_id' => 'classNameId'], ['returnTo' => '/admin/product/class_name']),
            self::adminGet('admin_product_class_category_sort_no_move', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']),
            self::adminPost('admin_product_class_category_sort_no_move', 'page://self/admin/sort-no-move', 'put', [], ['masterType' => 'classCategory']),
            self::adminGet('admin_product_class_category_visibility', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']),
            self::adminPost('admin_product_class_category_visibility', 'page://self/admin/toggle-visible', 'put', ['id' => 'rowId'], ['masterType' => 'classCategory']),
            self::adminGet('admin_product_class_name_delete', 'page://self/admin/class-name/class-name-list'),
            self::adminPost('admin_product_class_name_delete', 'page://self/admin/class-name/class-name', 'delete', ['id' => 'classNameId']),
            self::adminGetPost('admin_product_class_name_export', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']),
            self::adminGet('admin_product_class_name_sort_no_move', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']),
            self::adminPost('admin_product_class_name_sort_no_move', 'page://self/admin/sort-no-move', 'put', [], ['masterType' => 'className']),
            self::adminGet('admin_product_tag_delete', 'page://self/admin/tag/tag-list'),
            self::adminPost('admin_product_tag_delete', 'page://self/admin/tag/tag', 'delete', ['id' => 'tagId']),
            self::adminGet('admin_product_tag_sort_no_move', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/tag']),
            self::adminPost('admin_product_tag_sort_no_move', 'page://self/admin/sort-no-move', 'put', [], ['masterType' => 'tag']),

            // Shop settings.
            self::adminGetPost('admin_setting_shop', 'page://self/admin/base-info'),
            self::adminGet('admin_setting_shop_calendar', 'page://self/admin/calendar'),
            self::adminPost('admin_setting_shop_calendar', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_shop_calendar']),
            self::adminGet('admin_setting_shop_calendar_new', 'page://self/admin/calendar'),
            self::adminPost('admin_setting_shop_calendar_new', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_shop_calendar']),
            self::adminGetPost('admin_setting_shop_calendar_delete', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin_setting_shop_calendar']),
            self::adminGetPost('admin_setting_shop_csv', 'page://self/admin/csv-config', ['csv_type' => 'csvType']),
            self::adminGet('admin_setting_shop_delivery_new', 'page://self/admin/delivery/delivery'),
            self::adminPost('admin_setting_shop_delivery_new', 'page://self/admin/delivery/delivery-list', null, ['name' => 'deliveryName']),
            self::adminGet('admin_setting_shop_delivery_edit', 'page://self/admin/delivery/delivery', ['id' => 'deliveryId']),
            self::adminPost('admin_setting_shop_delivery_edit', 'page://self/admin/delivery/delivery', 'put', ['id' => 'deliveryId', 'name' => 'deliveryName']),
            self::adminGet('admin_setting_shop_delivery_delete', 'page://self/admin/delivery/delivery-list'),
            self::adminPost('admin_setting_shop_delivery_delete', 'page://self/admin/delivery/delivery', 'delete', ['id' => 'deliveryId']),
            self::adminGet('admin_setting_shop_delivery_sort_no_move', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/setting/shop/delivery']),
            self::adminPost('admin_setting_shop_delivery_sort_no_move', 'page://self/admin/sort-no-move', 'put', [], ['masterType' => 'delivery']),
            self::adminGet('admin_setting_shop_delivery_visibility', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/setting/shop/delivery']),
            self::adminPost('admin_setting_shop_delivery_visibility', 'page://self/admin/toggle-visible', 'put', ['id' => 'rowId'], ['masterType' => 'delivery']),
            self::adminGetPost('admin_setting_shop_mail', 'page://self/admin/mail-template', ['mail_subject' => 'mailSubject']),
            self::adminGetPost('admin_setting_shop_mail_delete', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin_setting_shop_mail']),
            self::adminGet('admin_setting_shop_order_status', 'page://self/admin/order-status'),
            self::adminPost('admin_setting_shop_order_status', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_shop_order_status']),
            self::adminGet('admin_setting_shop_payment_new', 'page://self/admin/payment/payment'),
            self::adminPost('admin_setting_shop_payment_new', 'page://self/admin/payment/payment-list', null, ['method' => 'paymentMethodName', 'rule_min' => 'ruleMin', 'rule_max' => 'ruleMax']),
            self::adminGet('admin_setting_shop_payment_edit', 'page://self/admin/payment/payment', ['id' => 'paymentId']),
            self::adminPost('admin_setting_shop_payment_edit', 'page://self/admin/payment/payment', 'put', ['id' => 'paymentId', 'method' => 'paymentMethodName', 'rule_min' => 'ruleMin', 'rule_max' => 'ruleMax']),
            self::adminGet('admin_setting_shop_payment_delete', 'page://self/admin/payment/payment-list'),
            self::adminPost('admin_setting_shop_payment_delete', 'page://self/admin/payment/payment', 'delete', ['id' => 'paymentId']),
            self::adminGet('admin_setting_shop_payment_sort_no_move', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/setting/shop/payment']),
            self::adminPost('admin_setting_shop_payment_sort_no_move', 'page://self/admin/sort-no-move', 'put', [], ['masterType' => 'payment']),
            self::adminGet('admin_setting_shop_payment_visible', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/setting/shop/payment']),
            self::adminPost('admin_setting_shop_payment_visible', 'page://self/admin/toggle-visible', 'put', ['id' => 'rowId'], ['masterType' => 'payment']),
            self::adminPost('admin_setting_shop_tax_new', 'page://self/admin/tax-rule/tax-rule-list', 'post', ['tax_rate' => 'taxRate', 'apply_date' => 'applyDate', 'rounding_type' => 'roundingType']),
            self::adminGet('admin_setting_shop_tax_delete', 'page://self/admin/tax-rule/tax-rule-list'),
            self::adminPost('admin_setting_shop_tax_delete', 'page://self/admin/tax-rule/tax-rule', 'delete', ['id' => 'taxRuleId']),
            self::adminGet('admin_setting_shop_tradelaw', 'page://self/admin/trade-law'),
            self::adminPost('admin_setting_shop_tradelaw', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_shop_tradelaw']),

            // System settings.
            self::adminGet('admin_setting_system_authority', 'page://self/admin/authority-role'),
            self::adminPost('admin_setting_system_authority', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_system_authority']),
            self::adminGet('admin_setting_system_masterdata', 'page://self/admin/master-data'),
            self::adminPost('admin_setting_system_masterdata', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_system_masterdata']),
            self::adminGet('admin_setting_system_masterdata_edit', 'page://self/admin/master-data'),
            self::adminPost('admin_setting_system_masterdata_edit', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_system_masterdata']),
            self::adminGet('admin_setting_system_member_new', 'page://self/admin/member'),
            self::adminPost('admin_setting_system_member_new', 'page://self/admin/member', null, ['login_id' => 'loginId', 'plain_password_first' => 'password']),
            self::adminGet('admin_setting_system_member_edit', 'page://self/admin/member', ['id' => 'loginId']),
            self::adminPost('admin_setting_system_member_edit', 'page://self/admin/member', 'put', ['id' => 'loginId', 'login_id' => 'loginId']),
            self::adminGet('admin_setting_system_member_delete', 'page://self/admin/member-list'),
            self::adminPost('admin_setting_system_member_delete', 'page://self/admin/member', 'delete', ['id' => 'loginId']),
            self::adminGetPost('admin_setting_system_member_up', 'page://self/admin/action-redirect', ['id' => 'loginId'], ['returnTo' => '/admin/setting/system/member']),
            self::adminGetPost('admin_setting_system_member_down', 'page://self/admin/action-redirect', ['id' => 'loginId'], ['returnTo' => '/admin/setting/system/member']),
            self::adminGet('admin_setting_system_security', 'page://self/admin/security'),
            self::adminPost('admin_setting_system_security', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_system_security']),
            self::adminGet('admin_setting_system_system_phpinfo', 'page://self/admin/system'),
            self::adminPost('admin_setting_system_system_phpinfo', 'page://self/admin/system', 'get'),

            // Store / plugin / template.
            self::adminGet('admin_store_plugin_owners_search_page', 'page://self/admin/plugin-list'),
            self::adminPost('admin_store_plugin_owners_search_page', 'page://self/admin/plugin-list', 'get'),
            self::adminGet('admin_store_plugin_enable', 'page://self/admin/plugin-list'),
            self::adminPost('admin_store_plugin_enable', 'page://self/admin/plugin-enable', 'post', ['code' => 'pluginCode']),
            self::adminGet('admin_store_plugin_disable', 'page://self/admin/plugin-list'),
            self::adminPost('admin_store_plugin_disable', 'page://self/admin/plugin-disable', 'post', ['code' => 'pluginCode']),
            self::adminGet('admin_store_plugin_install', 'page://self/admin/plugin-list'),
            self::adminPost('admin_store_plugin_install', 'page://self/admin/plugin-list', 'post', ['code' => 'pluginCode', 'version' => 'pluginVersion']),
            self::adminGet('admin_store_plugin_uninstall', 'page://self/admin/plugin-list'),
            self::adminPost('admin_store_plugin_uninstall', 'page://self/admin/plugin', 'delete', ['code' => 'pluginCode']),
            self::adminGet('admin_store_template', 'page://self/admin/template/template-list'),
            self::adminPost('admin_store_template', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_store_template']),
            self::adminGet('admin_store_template_install', 'page://self/admin/template/template-add'),
            self::adminPost('admin_store_template_install', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_store_template']),
            self::adminGetPost('admin_store_template_download', 'page://self/admin/action-redirect', ['id' => 'templateId'], ['returnTo' => '/admin_store_template']),
            self::adminGet('admin_store_template_delete', 'page://self/admin/template/template-list'),
            self::adminPost('admin_store_template_delete', 'page://self/admin/action-redirect', 'post', ['id' => 'templateId'], ['returnTo' => '/admin_store_template']),
            self::adminGet('admin_two_factor_auth', 'page://self/admin/two-factor-auth'),
            self::adminPost('admin_two_factor_auth', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_two_factor_auth']),
            self::adminGet('admin_two_factor_auth_set', 'page://self/admin/two-factor-auth-set'),
            self::adminPost('admin_two_factor_auth_set', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_two_factor_auth']),
        ];
    }

    /** @param array<string, string> $queryParamMap */
    private static function adminGet(string $name, string $resource, array $queryParamMap = [], array $defaults = []): Route
    {
        return new Route(
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
        string $name,
        string $resource,
        string|null $dispatchMethod = null,
        array $queryParamMap = [],
        array $defaults = [],
    ): Route {
        return new Route(
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
        string $name,
        string $resource,
        array $queryParamMap = [],
        array $defaults = [],
    ): Route {
        return new Route(
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
