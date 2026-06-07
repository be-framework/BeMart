<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use Twig\Environment;
use Twig\TwigFunction;

use function array_key_exists;
use function http_build_query;
use function str_replace;

/**
 * Registers `url()` / `path()` on a render-diff test's EC-CUBE-side Twig
 * Environment.
 *
 * The application no longer exposes EC-CUBE route names. This test-only
 * stub keeps the EC-CUBE reference templates renderable by translating their
 * legacy route names to BeMart's canonical Resource paths.
 */
final class EcCubeRouteStub
{
    /** @var array<string, array{path: string, aliases: array<string, string>, defaults: array<string, string>}> */
    private const ROUTES = [
        'homepage' => ['path' => '/', 'aliases' => [], 'defaults' => []],
        'block_cart' => ['path' => '/cart', 'aliases' => [], 'defaults' => []],
        'product_list' => ['path' => '/products', 'aliases' => [], 'defaults' => []],
        'product_detail' => ['path' => '/product', 'aliases' => ['id' => 'productCode'], 'defaults' => []],
        'cart' => ['path' => '/cart', 'aliases' => [], 'defaults' => []],
        'product_add_cart' => ['path' => '/cart/item', 'aliases' => ['id' => 'productCode'], 'defaults' => []],
        'cart_handle_item' => ['path' => '/cart', 'aliases' => [], 'defaults' => []],
        'contact' => ['path' => '/contact', 'aliases' => [], 'defaults' => []],
        'contact_confirm' => ['path' => '/contact/confirm', 'aliases' => [], 'defaults' => []],
        'contact_complete' => ['path' => '/contact/complete', 'aliases' => [], 'defaults' => []],
        'entry' => ['path' => '/entry', 'aliases' => [], 'defaults' => []],
        'entry_confirm' => ['path' => '/entry/confirm', 'aliases' => [], 'defaults' => []],
        'entry_complete' => ['path' => '/entry/complete', 'aliases' => [], 'defaults' => []],
        'entry_activate' => ['path' => '/entry/activate', 'aliases' => ['secret_key' => 'secretKey'], 'defaults' => []],
        'mypage_login' => ['path' => '/login', 'aliases' => [], 'defaults' => []],
        'logout' => ['path' => '/logout', 'aliases' => [], 'defaults' => []],
        'forgot' => ['path' => '/forgot-password', 'aliases' => [], 'defaults' => []],
        'forgot_complete' => ['path' => '/forgot-complete', 'aliases' => [], 'defaults' => []],
        'forgot_reset' => ['path' => '/reset', 'aliases' => ['reset_key' => 'resetKey'], 'defaults' => []],
        'mypage' => ['path' => '/mypage', 'aliases' => [], 'defaults' => []],
        'mypage_change' => ['path' => '/mypage/change', 'aliases' => [], 'defaults' => []],
        'mypage_change_complete' => ['path' => '/mypage/change-complete', 'aliases' => [], 'defaults' => []],
        'mypage_delivery' => ['path' => '/mypage/address-list', 'aliases' => [], 'defaults' => []],
        'mypage_delivery_new' => ['path' => '/mypage/address', 'aliases' => [], 'defaults' => []],
        'mypage_delivery_edit' => ['path' => '/mypage/address', 'aliases' => ['id' => 'addressId'], 'defaults' => []],
        'mypage_delivery_delete' => ['path' => '/mypage/address-list', 'aliases' => [], 'defaults' => []],
        'mypage_favorite' => ['path' => '/mypage/favorite-list', 'aliases' => [], 'defaults' => []],
        'mypage_favorite_action' => ['path' => '/mypage/favorite', 'aliases' => [], 'defaults' => []],
        'mypage_favorite_delete' => ['path' => '/mypage/favorite-list', 'aliases' => [], 'defaults' => []],
        'mypage_history' => ['path' => '/mypage/history', 'aliases' => ['order_no' => 'orderNo'], 'defaults' => []],
        'mypage_withdraw' => ['path' => '/mypage/withdraw', 'aliases' => [], 'defaults' => []],
        'mypage_withdraw_complete' => ['path' => '/mypage/withdraw-complete', 'aliases' => [], 'defaults' => []],
        'shopping' => ['path' => '/shopping', 'aliases' => [], 'defaults' => []],
        'shopping_shipping' => ['path' => '/shopping/shipping', 'aliases' => [], 'defaults' => []],
        'shopping_shipping_edit' => ['path' => '/shopping/shipping-edit', 'aliases' => [], 'defaults' => []],
        'shopping_shipping_multiple' => ['path' => '/shopping/shipping-multiple', 'aliases' => [], 'defaults' => []],
        'shopping_shipping_multiple_edit' => ['path' => '/shopping/shipping-multiple-edit', 'aliases' => [], 'defaults' => []],
        'shopping_login' => ['path' => '/shopping/login', 'aliases' => [], 'defaults' => []],
        'shopping_nonmember' => ['path' => '/shopping/non-member', 'aliases' => [], 'defaults' => []],
        'shopping_confirm' => ['path' => '/shopping/confirm', 'aliases' => [], 'defaults' => []],
        'shopping_checkout' => ['path' => '/shopping/checkout', 'aliases' => [], 'defaults' => []],
        'shopping_complete' => ['path' => '/shopping/complete', 'aliases' => [], 'defaults' => []],
        'shopping_error' => ['path' => '/shopping/error', 'aliases' => [], 'defaults' => []],
        'help_about' => ['path' => '/help/about', 'aliases' => [], 'defaults' => []],
        'help_guide' => ['path' => '/help/guide', 'aliases' => [], 'defaults' => []],
        'help_agreement' => ['path' => '/help/agreement', 'aliases' => [], 'defaults' => []],
        'help_privacy' => ['path' => '/help/privacy', 'aliases' => [], 'defaults' => []],
        'help_tradelaw' => ['path' => '/help/trade-law', 'aliases' => [], 'defaults' => []],
        'admin_login' => ['path' => '/admin/login', 'aliases' => [], 'defaults' => []],
        'admin_homepage' => ['path' => '/admin/index', 'aliases' => [], 'defaults' => []],
        'admin_logout' => ['path' => '/admin/logout', 'aliases' => [], 'defaults' => []],
        'admin_change_password' => ['path' => '/admin/change-password', 'aliases' => [], 'defaults' => []],
        'admin_product' => ['path' => '/admin/product', 'aliases' => [], 'defaults' => []],
        'admin_category_csv' => ['path' => '/admin/category/csv', 'aliases' => [], 'defaults' => []],
        'admin_product_tag' => ['path' => '/admin/tag/tag-list', 'aliases' => [], 'defaults' => []],
        'admin_product_class_name' => ['path' => '/admin/class-name/class-name-list', 'aliases' => [], 'defaults' => []],
        'admin_product_csv_class_name_path' => ['path' => '/admin/product/csv-class-name', 'aliases' => [], 'defaults' => []],
        'admin_product_csv_class_category_path' => ['path' => '/admin/product/csv-class-category', 'aliases' => [], 'defaults' => []],
        'admin_product_category' => ['path' => '/admin/category/category-list', 'aliases' => [], 'defaults' => []],
        'admin_order' => ['path' => '/admin/order', 'aliases' => [], 'defaults' => []],
        'admin_customer' => ['path' => '/admin/customer-list', 'aliases' => [], 'defaults' => []],
        'admin_customer_resend' => ['path' => '/admin/customer/resend-activation-mail', 'aliases' => [], 'defaults' => []],
        'admin_content_news' => ['path' => '/admin/news/news-list', 'aliases' => [], 'defaults' => []],
        'admin_content_page' => ['path' => '/admin/page/page-list', 'aliases' => [], 'defaults' => []],
        'admin_content_layout' => ['path' => '/admin/layout/layout-list', 'aliases' => [], 'defaults' => []],
        'admin_content_block' => ['path' => '/admin/block/block-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_payment' => ['path' => '/admin/payment/payment-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_delivery' => ['path' => '/admin/delivery/delivery-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_tax' => ['path' => '/admin/tax-rule/tax-rule-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_system_member' => ['path' => '/admin/member-list', 'aliases' => [], 'defaults' => []],
        'admin_content_block_new' => ['path' => '/admin/block/block', 'aliases' => [], 'defaults' => []],
        'admin_content_block_edit' => ['path' => '/admin/block/block', 'aliases' => ['id' => 'blockId'], 'defaults' => []],
        'admin_content_block_delete' => ['path' => '/admin/block/block-list', 'aliases' => [], 'defaults' => []],
        'admin_content_cache' => ['path' => '/admin/content/cache', 'aliases' => [], 'defaults' => []],
        'admin_content_css' => ['path' => '/admin/content/css', 'aliases' => [], 'defaults' => []],
        'admin_content_js' => ['path' => '/admin/content/js', 'aliases' => [], 'defaults' => []],
        'admin_content_layout_new' => ['path' => '/admin/layout/layout', 'aliases' => [], 'defaults' => []],
        'admin_content_layout_edit' => ['path' => '/admin/layout/layout', 'aliases' => ['id' => 'layoutId'], 'defaults' => []],
        'admin_content_maintenance' => ['path' => '/admin/content/maintenance', 'aliases' => [], 'defaults' => []],
        'admin_content_news_new' => ['path' => '/admin/news/news', 'aliases' => [], 'defaults' => []],
        'admin_content_news_edit' => ['path' => '/admin/news/news', 'aliases' => ['id' => 'newsId'], 'defaults' => []],
        'admin_content_news_delete' => ['path' => '/admin/news/news-list', 'aliases' => [], 'defaults' => []],
        'admin_content_page_new' => ['path' => '/admin/page/page', 'aliases' => [], 'defaults' => []],
        'admin_content_page_edit' => ['path' => '/admin/page/page', 'aliases' => ['id' => 'pageId'], 'defaults' => []],
        'admin_content_page_delete' => ['path' => '/admin/page/page-list', 'aliases' => [], 'defaults' => []],
        'admin_customer_edit' => ['path' => '/admin/customer', 'aliases' => ['id' => 'customerId'], 'defaults' => []],
        'admin_customer_delete' => ['path' => '/admin/customer-list', 'aliases' => [], 'defaults' => []],
        'admin_customer_export' => ['path' => '/admin/customer-csv', 'aliases' => [], 'defaults' => []],
        'admin_customer_delivery_new' => ['path' => '/admin/customer-delivery-edit', 'aliases' => ['id' => 'customerId'], 'defaults' => []],
        'admin_homepage_customer' => ['path' => '/admin/customer-list', 'aliases' => [], 'defaults' => []],
        'admin_homepage_nonstock' => ['path' => '/admin/product-list', 'aliases' => [], 'defaults' => []],
        'admin_homepage_sale' => ['path' => '/admin/order-list', 'aliases' => [], 'defaults' => []],
        'admin_order_edit' => ['path' => '/admin/order/edit', 'aliases' => ['id' => 'orderNo'], 'defaults' => []],
        'admin_order_bulk_delete' => ['path' => '/admin/order-list', 'aliases' => [], 'defaults' => []],
        'admin_order_csv_shipping' => ['path' => '/admin/order/import-shipping', 'aliases' => [], 'defaults' => []],
        'admin_order_export_order' => ['path' => '/admin/order/export-order', 'aliases' => [], 'defaults' => []],
        'admin_order_export_pdf' => ['path' => '/admin/order/export-order-pdf', 'aliases' => ['ids' => 'orderNos'], 'defaults' => []],
        'admin_order_export_shipping' => ['path' => '/admin/order/export-shipping', 'aliases' => [], 'defaults' => []],
        'admin_order_mail' => ['path' => '/admin/order/send-mail', 'aliases' => ['id' => 'orderNo'], 'defaults' => []],
        'admin_order_shipping' => ['path' => '/admin/order/shipping-address', 'aliases' => ['id' => 'orderNo'], 'defaults' => []],
        'admin_shipping_notify_mail' => ['path' => '/admin/order/edit', 'aliases' => ['id' => 'orderNo'], 'defaults' => []],
        'admin_shipping_preview_notify_mail' => ['path' => '/admin/order/mail-confirm', 'aliases' => ['id' => 'orderNo'], 'defaults' => []],
        'admin_shipping_update_order_status' => ['path' => '/admin/order-list', 'aliases' => [], 'defaults' => []],
        'admin_shipping_update_tracking_number' => ['path' => '/admin/order/edit', 'aliases' => ['id' => 'orderNo'], 'defaults' => []],
        'admin_product_product_new' => ['path' => '/admin/product-new', 'aliases' => [], 'defaults' => []],
        'admin_product_product_edit' => ['path' => '/admin/product/edit', 'aliases' => ['id' => 'productCode'], 'defaults' => []],
        'admin_product_product_delete' => ['path' => '/admin/product-list', 'aliases' => [], 'defaults' => []],
        'admin_product_product_copy' => ['path' => '/admin/product/edit', 'aliases' => ['id' => 'productCode'], 'defaults' => []],
        'admin_product_product_class' => ['path' => '/admin/product/product-class', 'aliases' => ['id' => 'productCode'], 'defaults' => []],
        'admin_product_bulk_product_status' => ['path' => '/admin/product-list', 'aliases' => [], 'defaults' => []],
        'admin_product_export' => ['path' => '/admin/product-csv', 'aliases' => [], 'defaults' => []],
        'admin_product_csv_product' => ['path' => '/admin/product-csv', 'aliases' => [], 'defaults' => []],
        'admin_product_csv_category' => ['path' => '/admin/category/csv', 'aliases' => [], 'defaults' => []],
        'admin_product_csv_class_name' => ['path' => '/admin/product/csv-class-name', 'aliases' => [], 'defaults' => []],
        'admin_product_csv_class_category' => ['path' => '/admin/product/csv-class-category', 'aliases' => [], 'defaults' => []],
        'admin_product_category_edit' => ['path' => '/admin/category/category', 'aliases' => ['id' => 'categoryId'], 'defaults' => []],
        'admin_product_class_category' => ['path' => '/admin/class-category/class-category-list', 'aliases' => ['class_name_id' => 'classNameId'], 'defaults' => []],
        'admin_product_class_category_edit' => ['path' => '/admin/class-category/class-category-list', 'aliases' => ['class_name_id' => 'classNameId', 'id' => 'classCategoryId'], 'defaults' => []],
        'admin_product_class_category_delete' => ['path' => '/admin/class-category/class-category-list', 'aliases' => ['class_name_id' => 'classNameId'], 'defaults' => []],
        'admin_product_class_category_export' => ['path' => '/admin/class-category/class-category-export', 'aliases' => ['class_name_id' => 'classNameId'], 'defaults' => []],
        'admin_product_class_category_sort_no_move' => ['path' => '/admin/class-category/class-category-list', 'aliases' => ['class_name_id' => 'classNameId'], 'defaults' => []],
        'admin_product_class_category_visibility' => ['path' => '/admin/class-category/class-category-list', 'aliases' => ['class_name_id' => 'classNameId'], 'defaults' => []],
        'admin_product_class_name_delete' => ['path' => '/admin/class-name/class-name-list', 'aliases' => [], 'defaults' => []],
        'admin_product_class_name_export' => ['path' => '/admin/class-name/class-name-export', 'aliases' => [], 'defaults' => []],
        'admin_product_class_name_sort_no_move' => ['path' => '/admin/class-name/class-name-list', 'aliases' => [], 'defaults' => []],
        'admin_product_tag_delete' => ['path' => '/admin/tag/tag-list', 'aliases' => [], 'defaults' => []],
        'admin_product_tag_sort_no_move' => ['path' => '/admin/tag/tag-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop' => ['path' => '/admin/base-info', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_calendar' => ['path' => '/admin/calendar', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_calendar_new' => ['path' => '/admin/calendar', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_calendar_delete' => ['path' => '/admin/calendar', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_csv' => ['path' => '/admin/csv-config', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_delivery_new' => ['path' => '/admin/delivery/delivery', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_delivery_edit' => ['path' => '/admin/delivery/delivery', 'aliases' => ['id' => 'deliveryId'], 'defaults' => []],
        'admin_setting_shop_delivery_delete' => ['path' => '/admin/delivery/delivery-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_delivery_sort_no_move' => ['path' => '/admin/delivery/delivery-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_delivery_visibility' => ['path' => '/admin/delivery/delivery-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_mail' => ['path' => '/admin/mail-template', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_mail_delete' => ['path' => '/admin/mail-template', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_order_status' => ['path' => '/admin/order-status', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_payment_new' => ['path' => '/admin/payment/payment', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_payment_edit' => ['path' => '/admin/payment/payment', 'aliases' => ['id' => 'paymentId'], 'defaults' => []],
        'admin_setting_shop_payment_delete' => ['path' => '/admin/payment/payment-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_payment_sort_no_move' => ['path' => '/admin/payment/payment-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_payment_visible' => ['path' => '/admin/payment/payment-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_tax_new' => ['path' => '/admin/tax-rule/tax-rule-list', 'aliases' => ['tax_rate' => 'taxRate', 'apply_date' => 'applyDate', 'rounding_type' => 'roundingType'], 'defaults' => []],
        'admin_setting_shop_tax_delete' => ['path' => '/admin/tax-rule/tax-rule-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_shop_tradelaw' => ['path' => '/admin/trade-law', 'aliases' => [], 'defaults' => []],
        'admin_setting_system_authority' => ['path' => '/admin/authority-role', 'aliases' => [], 'defaults' => []],
        'admin_setting_system_masterdata' => ['path' => '/admin/master-data', 'aliases' => [], 'defaults' => []],
        'admin_setting_system_masterdata_edit' => ['path' => '/admin/master-data', 'aliases' => [], 'defaults' => []],
        'admin_setting_system_member_new' => ['path' => '/admin/member', 'aliases' => [], 'defaults' => []],
        'admin_setting_system_member_edit' => ['path' => '/admin/member', 'aliases' => ['id' => 'loginId'], 'defaults' => []],
        'admin_setting_system_member_delete' => ['path' => '/admin/member-list', 'aliases' => [], 'defaults' => []],
        'admin_setting_system_member_up' => ['path' => '/admin/member-list', 'aliases' => ['id' => 'loginId'], 'defaults' => []],
        'admin_setting_system_member_down' => ['path' => '/admin/member-list', 'aliases' => ['id' => 'loginId'], 'defaults' => []],
        'admin_setting_system_security' => ['path' => '/admin/security', 'aliases' => [], 'defaults' => []],
        'admin_setting_system_system_phpinfo' => ['path' => '/admin/system', 'aliases' => [], 'defaults' => []],
        'admin_store_plugin_owners_search_page' => ['path' => '/admin/plugin-list', 'aliases' => [], 'defaults' => []],
        'admin_store_plugin_enable' => ['path' => '/admin/plugin-list', 'aliases' => [], 'defaults' => []],
        'admin_store_plugin_disable' => ['path' => '/admin/plugin-list', 'aliases' => [], 'defaults' => []],
        'admin_store_plugin_install' => ['path' => '/admin/plugin-list', 'aliases' => [], 'defaults' => []],
        'admin_store_plugin_uninstall' => ['path' => '/admin/plugin-list', 'aliases' => [], 'defaults' => []],
        'admin_store_template' => ['path' => '/admin/template/template-list', 'aliases' => [], 'defaults' => []],
        'admin_store_template_install' => ['path' => '/admin/template/template-add', 'aliases' => [], 'defaults' => []],
        'admin_store_template_download' => ['path' => '/admin/template/template-list', 'aliases' => ['id' => 'templateId'], 'defaults' => []],
        'admin_store_template_delete' => ['path' => '/admin/template/template-list', 'aliases' => [], 'defaults' => []],
        'admin_two_factor_auth' => ['path' => '/admin/two-factor-auth', 'aliases' => [], 'defaults' => []],
        'admin_two_factor_auth_set' => ['path' => '/admin/two-factor-auth-set', 'aliases' => [], 'defaults' => []],
    ];

    /** Register `url` and `path` on $twig for EC-CUBE reference templates. */
    public static function register(Environment $twig): void
    {
        /** @param array<string, mixed> $params */
        $resolve = static fn (string $route, array $params = []): string => self::resolve($route, $params);

        $twig->addFunction(new TwigFunction('url', $resolve));
        $twig->addFunction(new TwigFunction('path', $resolve));
    }

    /** @param array<string, mixed> $params */
    private static function resolve(string $route, array $params): string
    {
        if (! array_key_exists($route, self::ROUTES)) {
            return '/' . str_replace('_', '-', $route) . self::query($params);
        }

        $config = self::ROUTES[$route];
        $query = $config['defaults'];
        foreach ($params as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $query[$config['aliases'][$name] ?? $name] = (string) $value;
        }

        return $config['path'] . self::query($query);
    }

    /** @param array<string, mixed> $params */
    private static function query(array $params): string
    {
        if ($params === []) {
            return '';
        }

        return '?' . http_build_query($params, '', '&');
    }
}
