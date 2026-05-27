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
    /** @var array<non-empty-string, array<non-empty-string, non-empty-string>> */
    private const ORIGINAL_PATHS = [
        'admin_change_password' => ['GET' => '/admin/change_password', 'POST' => '/admin/change_password'],
        'admin_content_block' => ['GET' => '/admin/content/block'],
        'admin_content_block_delete' => ['POST' => '/admin/content/block/{id}/delete'],
        'admin_content_block_edit' => ['GET' => '/admin/content/block/{id}/edit', 'POST' => '/admin/content/block/{id}/edit'],
        'admin_content_block_new' => ['GET' => '/admin/content/block/new', 'POST' => '/admin/content/block/new'],
        'admin_content_cache' => ['GET' => '/admin/content/cache', 'POST' => '/admin/content/cache'],
        'admin_content_css' => ['GET' => '/admin/content/css', 'POST' => '/admin/content/css'],
        'admin_content_file' => ['GET' => '/admin/content/file_manager', 'POST' => '/admin/content/file_manager'],
        'admin_content_file_delete' => ['POST' => '/admin/content/file_delete'],
        'admin_content_file_download' => ['GET' => '/admin/content/file_download'],
        'admin_content_file_view' => ['GET' => '/admin/content/file_view'],
        'admin_content_js' => ['GET' => '/admin/content/js', 'POST' => '/admin/content/js'],
        'admin_content_layout' => ['GET' => '/admin/content/layout'],
        'admin_content_layout_delete' => ['POST' => '/admin/content/layout/{id}/delete'],
        'admin_content_layout_edit' => ['GET' => '/admin/content/layout/{id}/edit', 'POST' => '/admin/content/layout/{id}/edit'],
        'admin_content_layout_new' => ['GET' => '/admin/content/layout/new', 'POST' => '/admin/content/layout/new'],
        'admin_content_layout_preview' => ['POST' => '/admin/content/layout/{id}/preview'],
        'admin_content_layout_view_block' => ['GET' => '/admin/content/layout/view_block'],
        'admin_content_maintenance' => ['GET' => '/admin/content/maintenance', 'POST' => '/admin/content/maintenance'],
        'admin_content_news' => ['GET' => '/admin/content/news'],
        'admin_content_news_delete' => ['POST' => '/admin/content/news/{id}/delete'],
        'admin_content_news_edit' => ['GET' => '/admin/content/news/{id}/edit', 'POST' => '/admin/content/news/{id}/edit'],
        'admin_content_news_new' => ['GET' => '/admin/content/news/new', 'POST' => '/admin/content/news/new'],
        'admin_content_news_page' => ['GET' => '/admin/content/news/page/{page_no}'],
        'admin_content_page' => ['GET' => '/admin/content/page'],
        'admin_content_page_delete' => ['POST' => '/admin/content/page/{id}/delete'],
        'admin_content_page_edit' => ['GET' => '/admin/content/page/{id}/edit', 'POST' => '/admin/content/page/{id}/edit'],
        'admin_content_page_new' => ['GET' => '/admin/content/page/new', 'POST' => '/admin/content/page/new'],
        'admin_customer' => ['GET' => '/admin/customer', 'POST' => '/admin/customer'],
        'admin_customer_delete' => ['POST' => '/admin/customer/{id}/delete'],
        'admin_customer_delivery_delete' => ['POST' => '/admin/customer/{id}/delivery/{did}/delete'],
        'admin_customer_delivery_edit' => ['GET' => '/admin/customer/{id}/delivery/{did}/edit', 'POST' => '/admin/customer/{id}/delivery/{did}/edit'],
        'admin_customer_delivery_new' => ['GET' => '/admin/customer/{id}/delivery/new', 'POST' => '/admin/customer/{id}/delivery/new'],
        'admin_customer_edit' => ['GET' => '/admin/customer/{id}/edit', 'POST' => '/admin/customer/{id}/edit'],
        'admin_customer_export' => ['GET' => '/admin/customer/export'],
        'admin_customer_new' => ['GET' => '/admin/customer/new', 'POST' => '/admin/customer/new'],
        'admin_customer_page' => ['GET' => '/admin/customer/page/{page_no}', 'POST' => '/admin/customer/page/{page_no}'],
        'admin_customer_resend' => ['GET' => '/admin/customer/{id}/resend'],
        'admin_disable_maintenance' => ['POST' => '/admin/disable_maintenance/{mode}'],
        'admin_homepage' => ['GET' => '/admin'],
        'admin_homepage_customer' => ['GET' => '/admin/search_customer'],
        'admin_homepage_nonstock' => ['GET' => '/admin/search_nonstock'],
        'admin_homepage_sale' => ['GET' => '/admin/sale_chart'],
        'admin_login' => ['GET' => '/admin/login', 'POST' => '/admin/login'],
        'admin_order' => ['GET' => '/admin/order', 'POST' => '/admin/order'],
        'admin_order_bulk_delete' => ['POST' => '/admin/order/bulk_delete'],
        'admin_order_edit' => ['GET' => '/admin/order/{id}/edit', 'POST' => '/admin/order/{id}/edit'],
        'admin_order_export_order' => ['GET' => '/admin/order/export/order'],
        'admin_order_export_pdf' => ['GET' => '/admin/order/export/pdf', 'POST' => '/admin/order/export/pdf'],
        'admin_order_export_shipping' => ['GET' => '/admin/order/export/shipping'],
        'admin_order_mail' => ['GET' => '/admin/order/{id}/mail', 'POST' => '/admin/order/{id}/mail'],
        'admin_order_new' => ['GET' => '/admin/order/new', 'POST' => '/admin/order/new'],
        'admin_order_page' => ['GET' => '/admin/order/page/{page_no}', 'POST' => '/admin/order/page/{page_no}'],
        'admin_order_pdf_download' => ['POST' => '/admin/order/export/pdf/download'],
        'admin_order_search_customer_by_id' => ['POST' => '/admin/order/search/customer/id'],
        'admin_order_search_customer_html' => ['GET' => '/admin/order/search/customer/html', 'POST' => '/admin/order/search/customer/html'],
        'admin_order_search_customer_html_page' => ['GET' => '/admin/order/search/customer/html/page/{page_no}', 'POST' => '/admin/order/search/customer/html/page/{page_no}'],
        'admin_order_search_order_item_type' => ['POST' => '/admin/order/search/order_item_type'],
        'admin_order_search_product' => ['GET' => '/admin/order/search/product', 'POST' => '/admin/order/search/product'],
        'admin_order_search_product_page' => ['GET' => '/admin/order/search/product/page/{page_no}', 'POST' => '/admin/order/search/product/page/{page_no}'],
        'admin_payment_image_load' => ['GET' => '/admin/setting/shop/payment/image/load'],
        'admin_payment_image_process' => ['POST' => '/admin/setting/shop/payment/image/process'],
        'admin_payment_image_revert' => ['POST' => '/admin/setting/shop/payment/image/revert'],
        'admin_product' => ['GET' => '/admin/product', 'POST' => '/admin/product'],
        'admin_product_bulk_product_status' => ['POST' => '/admin/product/bulk/product-status/{id}'],
        'admin_product_category' => ['GET' => '/admin/product/category', 'POST' => '/admin/product/category'],
        'admin_product_category_csv_import' => ['GET' => '/admin/product/category_csv_upload', 'POST' => '/admin/product/category_csv_upload'],
        'admin_product_category_delete' => ['POST' => '/admin/product/category/{id}/delete'],
        'admin_product_category_edit' => ['GET' => '/admin/product/category/{id}/edit', 'POST' => '/admin/product/category/{id}/edit'],
        'admin_product_category_export' => ['GET' => '/admin/product/category/export'],
        'admin_product_category_show' => ['GET' => '/admin/product/category/{parent_id}', 'POST' => '/admin/product/category/{parent_id}'],
        'admin_product_category_sort_no_move' => ['POST' => '/admin/product/category/sort_no/move'],
        'admin_product_class_category' => ['GET' => '/admin/product/class_category/{class_name_id}', 'POST' => '/admin/product/class_category/{class_name_id}'],
        'admin_product_class_category_csv_import' => ['GET' => '/admin/product/class_category_csv_upload', 'POST' => '/admin/product/class_category_csv_upload'],
        'admin_product_class_category_delete' => ['POST' => '/admin/product/class_category/{class_name_id}/{id}/delete'],
        'admin_product_class_category_edit' => ['GET' => '/admin/product/class_category/{class_name_id}/{id}/edit', 'POST' => '/admin/product/class_category/{class_name_id}/{id}/edit'],
        'admin_product_class_category_export' => ['GET' => '/admin/product/class_category/export/{class_name_id}'],
        'admin_product_class_category_sort_no_move' => ['POST' => '/admin/product/class_category/sort_no/move'],
        'admin_product_class_category_visibility' => ['POST' => '/admin/product/class_category/{class_name_id}/{id}/visibility'],
        'admin_product_class_name' => ['GET' => '/admin/product/class_name', 'POST' => '/admin/product/class_name'],
        'admin_product_class_name_csv_import' => ['GET' => '/admin/product/class_name_csv_upload', 'POST' => '/admin/product/class_name_csv_upload'],
        'admin_product_class_name_delete' => ['POST' => '/admin/product/class_name/{id}/delete'],
        'admin_product_class_name_edit' => ['GET' => '/admin/product/class_name/{id}/edit', 'POST' => '/admin/product/class_name/{id}/edit'],
        'admin_product_class_name_export' => ['GET' => '/admin/product/class_name/export'],
        'admin_product_class_name_sort_no_move' => ['POST' => '/admin/product/class_name/sort_no/move'],
        'admin_product_classes_load' => ['GET' => '/admin/product/classes/{id}/load'],
        'admin_product_csv_import' => ['GET' => '/admin/product/product_csv_upload', 'POST' => '/admin/product/product_csv_upload'],
        'admin_product_csv_split' => ['POST' => '/admin/product/csv_split'],
        'admin_product_csv_split_cleanup' => ['POST' => '/admin/product/csv_split_cleanup'],
        'admin_product_csv_split_import' => ['POST' => '/admin/product/csv_split_import'],
        'admin_product_csv_template' => ['GET' => '/admin/product/csv_template/{type}'],
        'admin_product_export' => ['GET' => '/admin/product/export'],
        'admin_product_image_load' => ['GET' => '/admin/product/product/image/load'],
        'admin_product_image_process' => ['POST' => '/admin/product/product/image/process'],
        'admin_product_image_revert' => ['POST' => '/admin/product/product/image/revert'],
        'admin_product_page' => ['GET' => '/admin/product/page/{page_no}', 'POST' => '/admin/product/page/{page_no}'],
        'admin_product_product_class' => ['GET' => '/admin/product/product/class/{id}', 'POST' => '/admin/product/product/class/{id}'],
        'admin_product_product_class_clear' => ['POST' => '/admin/product/product/class/{id}/clear'],
        'admin_product_product_copy' => ['POST' => '/admin/product/product/{id}/copy'],
        'admin_product_product_delete' => ['POST' => '/admin/product/product/{id}/delete'],
        'admin_product_product_edit' => ['GET' => '/admin/product/product/{id}/edit', 'POST' => '/admin/product/product/{id}/edit'],
        'admin_product_product_new' => ['GET' => '/admin/product/product/new', 'POST' => '/admin/product/product/new'],
        'admin_product_tag' => ['GET' => '/admin/product/tag', 'POST' => '/admin/product/tag'],
        'admin_product_tag_delete' => ['POST' => '/admin/product/tag/{id}/delete'],
        'admin_product_tag_sort_no_move' => ['POST' => '/admin/product/tag/sort_no/move'],
        'admin_setting_shop' => ['GET' => '/admin/setting/shop', 'POST' => '/admin/setting/shop'],
        'admin_setting_shop_calendar' => ['GET' => '/admin/setting/shop/calendar', 'POST' => '/admin/setting/shop/calendar'],
        'admin_setting_shop_calendar_delete' => ['POST' => '/admin/setting/shop/calendar/{id}/delete'],
        'admin_setting_shop_calendar_new' => ['GET' => '/admin/setting/shop/calendar/new', 'POST' => '/admin/setting/shop/calendar/new'],
        'admin_setting_shop_csv' => ['GET' => '/admin/setting/shop/csv/{id}', 'POST' => '/admin/setting/shop/csv/{id}'],
        'admin_setting_shop_delivery' => ['GET' => '/admin/setting/shop/delivery'],
        'admin_setting_shop_delivery_delete' => ['POST' => '/admin/setting/shop/delivery/{id}/delete'],
        'admin_setting_shop_delivery_edit' => ['GET' => '/admin/setting/shop/delivery/{id}/edit', 'POST' => '/admin/setting/shop/delivery/{id}/edit'],
        'admin_setting_shop_delivery_new' => ['GET' => '/admin/setting/shop/delivery/new', 'POST' => '/admin/setting/shop/delivery/new'],
        'admin_setting_shop_delivery_sort_no_move' => ['POST' => '/admin/setting/shop/delivery/sort_no/move'],
        'admin_setting_shop_delivery_visibility' => ['POST' => '/admin/setting/shop/delivery/{id}/visibility'],
        'admin_setting_shop_mail' => ['GET' => '/admin/setting/shop/mail', 'POST' => '/admin/setting/shop/mail'],
        'admin_setting_shop_mail_delete' => ['POST' => '/admin/setting/shop/mail/{id}/delete'],
        'admin_setting_shop_mail_edit' => ['GET' => '/admin/setting/shop/mail/{id}', 'POST' => '/admin/setting/shop/mail/{id}'],
        'admin_setting_shop_mail_preview' => ['POST' => '/admin/setting/shop/mail/preview'],
        'admin_setting_shop_order_status' => ['GET' => '/admin/setting/shop/order_status', 'POST' => '/admin/setting/shop/order_status'],
        'admin_setting_shop_payment' => ['GET' => '/admin/setting/shop/payment'],
        'admin_setting_shop_payment_delete' => ['POST' => '/admin/setting/shop/payment/{id}/delete'],
        'admin_setting_shop_payment_edit' => ['GET' => '/admin/setting/shop/payment/{id}/edit', 'POST' => '/admin/setting/shop/payment/{id}/edit'],
        'admin_setting_shop_payment_new' => ['GET' => '/admin/setting/shop/payment/new', 'POST' => '/admin/setting/shop/payment/new'],
        'admin_setting_shop_payment_sort_no_move' => ['POST' => '/admin/setting/shop/payment/sort_no/move'],
        'admin_setting_shop_payment_visible' => ['POST' => '/admin/setting/shop/payment/{id}/visible'],
        'admin_setting_shop_tax' => ['GET' => '/admin/setting/shop/tax', 'POST' => '/admin/setting/shop/tax'],
        'admin_setting_shop_tax_delete' => ['POST' => '/admin/setting/shop/tax/{id}/delete'],
        'admin_setting_shop_tax_new' => ['GET' => '/admin/setting/shop/tax/new', 'POST' => '/admin/setting/shop/tax/new'],
        'admin_setting_shop_tradelaw' => ['GET' => '/admin/setting/shop/tradelaw', 'POST' => '/admin/setting/shop/tradelaw'],
        'admin_setting_system_authority' => ['GET' => '/admin/setting/system/authority', 'POST' => '/admin/setting/system/authority'],
        'admin_setting_system_log' => ['GET' => '/admin/setting/system/log', 'POST' => '/admin/setting/system/log'],
        'admin_setting_system_login_history' => ['GET' => '/admin/setting/system/login_history', 'POST' => '/admin/setting/system/login_history'],
        'admin_setting_system_login_history_page' => ['GET' => '/admin/setting/system/login_history/{page_no}', 'POST' => '/admin/setting/system/login_history/{page_no}'],
        'admin_setting_system_masterdata' => ['GET' => '/admin/setting/system/masterdata', 'POST' => '/admin/setting/system/masterdata'],
        'admin_setting_system_masterdata_edit' => ['GET' => '/admin/setting/system/masterdata/edit', 'POST' => '/admin/setting/system/masterdata/edit'],
        'admin_setting_system_masterdata_view' => ['GET' => '/admin/setting/system/masterdata/{entity}/edit', 'POST' => '/admin/setting/system/masterdata/{entity}/edit'],
        'admin_setting_system_member' => ['GET' => '/admin/setting/system/member', 'POST' => '/admin/setting/system/member'],
        'admin_setting_system_member_delete' => ['POST' => '/admin/setting/system/member/{id}/delete'],
        'admin_setting_system_member_down' => ['POST' => '/admin/setting/system/member/{id}/down'],
        'admin_setting_system_member_edit' => ['GET' => '/admin/setting/system/member/{id}/edit', 'POST' => '/admin/setting/system/member/{id}/edit'],
        'admin_setting_system_member_new' => ['GET' => '/admin/setting/system/member/new', 'POST' => '/admin/setting/system/member/new'],
        'admin_setting_system_member_up' => ['POST' => '/admin/setting/system/member/{id}/up'],
        'admin_setting_system_security' => ['GET' => '/admin/setting/system/security', 'POST' => '/admin/setting/system/security'],
        'admin_setting_system_system' => ['GET' => '/admin/setting/system/system'],
        'admin_setting_system_system_phpinfo' => ['GET' => '/admin/setting/system/system/phpinfo'],
        'admin_setting_system_two_factor_auth_edit' => ['GET' => '/admin/setting/system/two_factor_auth/edit', 'POST' => '/admin/setting/system/two_factor_auth/edit'],
        'admin_shipping_csv_import' => ['GET' => '/admin/order/shipping_csv_upload', 'POST' => '/admin/order/shipping_csv_upload'],
        'admin_shipping_csv_template' => ['GET' => '/admin/order/csv_template'],
        'admin_shipping_edit' => ['GET' => '/admin/shipping/{id}/edit', 'POST' => '/admin/shipping/{id}/edit'],
        'admin_shipping_notify_mail' => ['POST' => '/admin/shipping/notify_mail/{id}'],
        'admin_shipping_preview_notify_mail' => ['GET' => '/admin/shipping/preview_notify_mail/{id}'],
        'admin_shipping_update_order_status' => ['POST' => '/admin/shipping/{id}/order_status'],
        'admin_shipping_update_tracking_number' => ['POST' => '/admin/shipping/{id}/tracking_number'],
        'admin_store_authentication_setting' => ['GET' => '/admin/store/plugin/authentication_setting', 'POST' => '/admin/store/plugin/authentication_setting'],
        'admin_store_plugin' => ['GET' => '/admin/store/plugin'],
        'admin_store_plugin_api_install' => ['POST' => '/admin/store/plugin/api/install'],
        'admin_store_plugin_api_schema_update' => ['POST' => '/admin/store/plugin/api/schema_update'],
        'admin_store_plugin_api_uninstall' => ['POST' => '/admin/store/plugin/api/delete/{id}/uninstall'],
        'admin_store_plugin_api_update' => ['POST' => '/admin/store/plugin/api/update'],
        'admin_store_plugin_api_upgrade' => ['POST' => '/admin/store/plugin/api/upgrade'],
        'admin_store_plugin_disable' => ['POST' => '/admin/store/plugin/{id}/disable'],
        'admin_store_plugin_enable' => ['POST' => '/admin/store/plugin/{id}/enable'],
        'admin_store_plugin_install' => ['GET' => '/admin/store/plugin/install', 'POST' => '/admin/store/plugin/install'],
        'admin_store_plugin_install_confirm' => ['GET' => '/admin/store/plugin/api/install/{id}/confirm'],
        'admin_store_plugin_owners_search' => ['GET' => '/admin/store/plugin/api/search', 'POST' => '/admin/store/plugin/api/search'],
        'admin_store_plugin_owners_search_page' => ['GET' => '/admin/store/plugin/api/search/page/{page_no}', 'POST' => '/admin/store/plugin/api/search/page/{page_no}'],
        'admin_store_plugin_uninstall' => ['POST' => '/admin/store/plugin/{id}/uninstall'],
        'admin_store_plugin_update' => ['POST' => '/admin/store/plugin/{id}/update'],
        'admin_store_plugin_update_confirm' => ['GET' => '/admin/store/plugin/api/upgrade/{id}/confirm'],
        'admin_store_template' => ['GET' => '/admin/store/template', 'POST' => '/admin/store/template'],
        'admin_store_template_delete' => ['POST' => '/admin/store/template/{id}/delete'],
        'admin_store_template_download' => ['GET' => '/admin/store/template/{id}/download'],
        'admin_store_template_install' => ['GET' => '/admin/store/template/install', 'POST' => '/admin/store/template/install'],
        'admin_two_factor_auth' => ['GET' => '/admin/two_factor_auth', 'POST' => '/admin/two_factor_auth'],
        'admin_two_factor_auth_set' => ['GET' => '/admin/two_factor_auth/set', 'POST' => '/admin/two_factor_auth/set'],
        'block_auto_new_item' => ['GET' => '/block/auto_new_item'],
        'block_calendar' => ['GET' => '/block/calendar'],
        'block_cart' => ['GET' => '/block/cart'],
        'block_cart_sp' => ['GET' => '/block/cart_sp'],
        'block_search_product' => ['GET' => '/block/search_product'],
        'block_search_product_sp' => ['GET' => '/block/search_product_sp'],
        'cart' => ['GET' => '/cart'],
        'cart_buystep' => ['GET' => '/cart/buystep/{cart_key}'],
        'cart_handle_item' => ['POST' => '/cart/{operation}/{productClassId}'],
        'contact' => ['GET' => '/contact', 'POST' => '/contact'],
        'contact_complete' => ['GET' => '/contact/complete'],
        'contact_confirm' => ['GET' => '/contact', 'POST' => '/contact'],
        'entry' => ['GET' => '/entry', 'POST' => '/entry'],
        'entry_activate' => ['GET' => '/entry/activate/{secret_key}/{qtyInCart}'],
        'entry_complete' => ['POST' => '/entry'],
        'forgot' => ['GET' => '/forgot', 'POST' => '/forgot'],
        'forgot_complete' => ['GET' => '/forgot/complete'],
        'forgot_reset' => ['GET' => '/forgot/reset/{reset_key}', 'POST' => '/forgot/reset/{reset_key}'],
        'help_about' => ['GET' => '/help/about'],
        'help_agreement' => ['GET' => '/help/agreement'],
        'help_guide' => ['GET' => '/help/guide'],
        'help_privacy' => ['GET' => '/help/privacy'],
        'help_tradelaw' => ['GET' => '/help/tradelaw'],
        'homepage' => ['GET' => '/'],
        'install' => ['GET' => '/install'],
        'install_complete' => ['GET' => '/install/complete'],
        'install_plugin_check_api' => ['POST' => '/install/plugin/check_api'],
        'install_plugin_enable' => ['POST' => '/install/plugin/{code}/enable'],
        'install_plugin_redirect' => ['GET' => '/install/plugin/redirect'],
        'install_plugins' => ['GET' => '/install/plugins'],
        'install_step1' => ['GET' => '/install/step1', 'POST' => '/install/step1'],
        'install_step2' => ['GET' => '/install/step2'],
        'install_step3' => ['GET' => '/install/step3', 'POST' => '/install/step3'],
        'install_step4' => ['GET' => '/install/step4', 'POST' => '/install/step4'],
        'install_step5' => ['GET' => '/install/step5', 'POST' => '/install/step5'],
        'mypage' => ['GET' => '/mypage'],
        'mypage_change' => ['GET' => '/mypage/change', 'POST' => '/mypage/change'],
        'mypage_change_complete' => ['GET' => '/mypage/change_complete'],
        'mypage_delivery' => ['GET' => '/mypage/delivery'],
        'mypage_delivery_delete' => ['POST' => '/mypage/delivery/{id}/delete'],
        'mypage_delivery_edit' => ['GET' => '/mypage/delivery/{id}/edit', 'POST' => '/mypage/delivery/{id}/edit'],
        'mypage_delivery_new' => ['GET' => '/mypage/delivery/new', 'POST' => '/mypage/delivery/new'],
        'mypage_favorite' => ['GET' => '/mypage/favorite'],
        'mypage_favorite_delete' => ['POST' => '/mypage/favorite/{id}/delete'],
        'mypage_history' => ['GET' => '/mypage/history/{order_no}'],
        'mypage_login' => ['GET' => '/mypage/login', 'POST' => '/mypage/login'],
        'mypage_order' => ['POST' => '/mypage/order/{order_no}'],
        'mypage_withdraw' => ['GET' => '/mypage/withdraw', 'POST' => '/mypage/withdraw'],
        'mypage_withdraw_complete' => ['GET' => '/mypage/withdraw/complete'],
        'mypage_withdraw_confirm' => ['GET' => '/mypage/withdraw/confirm'],
        'product_add_cart' => ['POST' => '/products/add_cart/{id}'],
        'product_add_favorite' => ['GET' => '/products/add_favorite/{id}', 'POST' => '/products/add_favorite/{id}'],
        'product_delete_favorite' => ['POST' => '/products/delete_favorite/{id}'],
        'product_detail' => ['GET' => '/products/detail/{id}'],
        'product_list' => ['GET' => '/products/list'],
        'shopping' => ['GET' => '/shopping'],
        'shopping_checkout' => ['POST' => '/shopping/checkout'],
        'shopping_complete' => ['GET' => '/shopping/complete'],
        'shopping_confirm' => ['POST' => '/shopping/confirm'],
        'shopping_customer' => ['POST' => '/shopping/customer'],
        'shopping_error' => ['GET' => '/shopping/error'],
        'shopping_login' => ['GET' => '/shopping/login'],
        'shopping_nonmember' => ['GET' => '/shopping/nonmember', 'POST' => '/shopping/nonmember'],
        'shopping_redirect_to' => ['POST' => '/shopping/redirect_to'],
        'shopping_shipping' => ['GET' => '/shopping/shipping/{id}', 'POST' => '/shopping/shipping/{id}'],
        'shopping_shipping_edit' => ['GET' => '/shopping/shipping_edit/{id}', 'POST' => '/shopping/shipping_edit/{id}'],
        'shopping_shipping_multiple' => ['GET' => '/shopping/shipping_multiple', 'POST' => '/shopping/shipping_multiple'],
        'shopping_shipping_multiple_edit' => ['GET' => '/shopping/shipping_multiple_edit', 'POST' => '/shopping/shipping_multiple_edit'],
        'sitemap_category_xml' => ['GET' => '/sitemap_category.xml'],
        'sitemap_page_xml' => ['GET' => '/sitemap_page.xml'],
        'sitemap_product_xml' => ['GET' => '/sitemap_product_{page}.xml'],
        'sitemap_xml' => ['GET' => '/sitemap.xml'],
        'user_data' => ['GET' => '/user_data/{route}'],
    ];

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
            new Route(
                'product_add_favorite',
                ['GET'],
                '/products/add_favorite/{id}',
                'page://self/product',
                ['id' => 'productCode'],
            ),
            new Route(
                'product_add_favorite',
                ['POST'],
                '/products/add_favorite/{id}',
                'page://self/mypage/favorite',
                ['id' => 'productCode'],
            ),
            new Route(
                'product_delete_favorite',
                ['POST'],
                '/products/delete_favorite/{id}',
                'page://self/mypage/favorite',
                ['id' => 'productCode'],
                'delete',
            ),
            // `cart_handle_item` is the BeMart port's own helper name for the
            // quantity up/down/remove controls in Cart.html.twig (EC-CUBE 4.3
            // splits these across cart_up/cart_down/cart_remove). HTML exposes
            // GET/POST only: GET falls back to the cart page, POST calls the
            // Cart/Item resource.
            new Route('cart_handle_item', ['GET'], '/cart/item', 'page://self/cart'),
            new Route('cart_handle_item', ['POST'], '/cart/item', 'page://self/cart/item'),
            new Route(
                'cart_buystep',
                ['GET'],
                '/cart/buystep/{cart_key}',
                'page://self/cart/buy-step',
                ['cart_key' => 'cartKey'],
            ),

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
            new Route('mypage_delivery_delete', ['POST'], '/mypage/delivery/delete', 'page://self/mypage/address', [], 'delete', [], ['id' => 'addressId']),
            new Route('mypage_favorite', ['GET'], '/mypage/favorite', 'page://self/mypage/favorite-list'),
            new Route('mypage_favorite_delete', ['POST'], '/mypage/favorite/delete', 'page://self/mypage/favorite', [], 'delete', [], ['id' => 'productCode']),
            new Route(
                'mypage_history',
                ['GET'],
                '/mypage/history/{order_no}',
                'page://self/mypage/history',
                ['order_no' => 'orderNo'],
            ),
            new Route(
                'mypage_order',
                ['POST'],
                '/mypage/order/{order_no}',
                'page://self/mypage/reorder',
                ['order_no' => 'orderNo'],
            ),
            new Route('mypage_withdraw', ['GET', 'POST'], '/mypage/withdraw', 'page://self/mypage/withdraw'),
            new Route(
                'mypage_withdraw_confirm',
                ['GET'],
                '/mypage/withdraw/confirm',
                'page://self/mypage/withdraw-confirm',
            ),
            new Route(
                'mypage_withdraw_complete',
                ['GET'],
                '/mypage/withdraw/complete',
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
            new Route(
                'shopping_customer',
                ['POST'],
                '/shopping/customer',
                'page://self/shopping/non-member',
                [],
                null,
                [],
                [
                    'customer_name01' => 'name01',
                    'customer_name02' => 'name02',
                    'customer_kana01' => 'kana01',
                    'customer_kana02' => 'kana02',
                    'customer_email' => 'email',
                    'customer_phone_number' => 'phoneNumber',
                    'customer_postal_code' => 'postalCode',
                    'customer_pref' => 'pref',
                    'customer_addr01' => 'addr01',
                    'customer_addr02' => 'addr02',
                ],
            ),
            new Route(
                'shopping_redirect_to',
                ['POST'],
                '/shopping/redirect_to',
                'page://self/shopping/redirect-to',
                [],
                null,
                [],
                ['redirect_to' => 'redirectTo'],
            ),
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
            self::adminPost('admin_content_css', 'page://self/admin/content/css'),
            self::adminGet('admin_content_js', 'page://self/admin/content/js'),
            self::adminPost('admin_content_js', 'page://self/admin/content/js'),
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
            self::adminGetPost('admin_setting_shop_csv', 'page://self/admin/csv-config', ['id' => 'id', 'csv_type' => 'csvType'], ['id' => '1']),
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
            self::adminGet('admin_store_plugin', 'page://self/admin/plugin-list'),
            self::adminGet('admin_store_plugin_owners_search_page', 'page://self/admin/plugin-list'),
            self::adminPost('admin_store_plugin_owners_search_page', 'page://self/admin/plugin-list', 'get'),
            self::adminPost('admin_store_plugin_enable', 'page://self/admin/plugin-enable', 'post', ['id' => 'pluginCode', 'code' => 'pluginCode']),
            self::adminGet('admin_store_plugin_enable', 'page://self/admin/plugin-list'),
            self::adminPost('admin_store_plugin_disable', 'page://self/admin/plugin-disable', 'post', ['id' => 'pluginCode', 'code' => 'pluginCode']),
            self::adminGet('admin_store_plugin_disable', 'page://self/admin/plugin-list'),
            self::adminGet('admin_store_plugin_install', 'page://self/admin/plugin-list'),
            self::adminPost('admin_store_plugin_install', 'page://self/admin/plugin-list', 'post', ['code' => 'pluginCode', 'version' => 'pluginVersion']),
            self::adminPost('admin_store_plugin_uninstall', 'page://self/admin/plugin', 'delete', ['id' => 'pluginCode', 'code' => 'pluginCode']),
            self::adminGet('admin_store_plugin_uninstall', 'page://self/admin/plugin-list'),
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
            self::originalPath($name, 'GET'),
            $resource,
            $queryParamMap,
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
            self::originalPath($name, 'POST'),
            $resource,
            $queryParamMap,
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
            self::originalPath($name, 'GET'),
            $resource,
            $queryParamMap,
            null,
            $defaults,
            $queryParamMap,
        );
    }

    private static function originalPath(string $name, string $method): string
    {
        $paths = self::ORIGINAL_PATHS[$name] ?? [];
        if (isset($paths[$method])) {
            return $paths[$method];
        }

        if ($method === 'POST') {
            foreach (['PUT', 'DELETE', 'GET'] as $fallbackMethod) {
                if (isset($paths[$fallbackMethod])) {
                    return $paths[$fallbackMethod];
                }
            }
        }

        return '/' . $name;
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
