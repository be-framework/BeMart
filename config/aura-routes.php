<?php

declare(strict_types=1);

use Aura\Router\Exception\RouteNotFound as AuraRouteNotFound;
use Aura\Router\Map;
use Aura\Router\Route as AuraRoute;


/**
 * Aura.Router map builder for EC-CUBE-compatible HTML routes.
 *
 * Aura owns path matching, placeholder extraction, and URL generation.
 * BEAR\Resource owns method dispatch and 405 responses.
 * BeMart adds only route extras Aura does not know about: BEAR resource URI,
 * internal dispatch method, parameter-name aliases, and ALPS descriptor IDs.
 *
 * @return callable(Map): null
 */

$alpsIds = [
        'admin_change_password' => ['GET' => 'goChangePassword', 'POST' => 'doChangePassword'],
        'admin_content_block' => 'goBlockList',
        'admin_content_block_delete' => ['GET' => 'goBlockList', 'POST' => 'doDeleteBlock'],
        'admin_content_block_edit' => ['GET' => 'goBlock', 'POST' => 'doUpdateBlock'],
        'admin_content_block_new' => ['GET' => 'goBlock', 'POST' => 'doCreateBlock'],
        'admin_content_cache' => ['GET' => 'goContentCache', 'POST' => 'doClearCache'],
        'admin_content_css' => ['GET' => 'goContentCss', 'POST' => 'doUpdateContentCss'],
        'admin_content_js' => ['GET' => 'goContentJs', 'POST' => 'doUpdateContentJs'],
        'admin_content_layout' => 'goLayoutList',
        'admin_content_layout_edit' => ['GET' => 'goLayout', 'POST' => 'doUpdateLayout'],
        'admin_content_layout_new' => ['GET' => 'goLayout', 'POST' => 'doUpdateLayout'],
        'admin_content_maintenance' => ['GET' => 'goMaintenance', 'POST' => 'doToggleMaintenance'],
        'admin_content_news' => 'goNewsList',
        'admin_content_news_delete' => ['GET' => 'goNewsList', 'POST' => 'doDeleteNews'],
        'admin_content_news_edit' => ['GET' => 'goNews', 'POST' => 'doUpdateNews'],
        'admin_content_news_new' => ['GET' => 'goNews', 'POST' => 'doCreateNews'],
        'admin_content_page' => 'goPageList',
        'admin_content_page_delete' => ['GET' => 'goPageList', 'POST' => 'doDeletePage'],
        'admin_content_page_edit' => ['GET' => 'goPage', 'POST' => 'doUpdatePage'],
        'admin_content_page_new' => ['GET' => 'goPage', 'POST' => 'doCreatePage'],
        'admin_customer' => 'goCustomerList',
        'admin_customer_delete' => ['GET' => 'goCustomerList', 'POST' => 'doDeleteCustomer'],
        'admin_customer_delivery_new' => ['GET' => 'goCustomerAddress', 'POST' => 'doCreateCustomerAddress'],
        'admin_customer_edit' => ['GET' => 'goCustomer', 'POST' => 'doUpdateCustomer'],
        'admin_customer_export' => 'goExportCustomer',
        'admin_customer_resend' => 'doResendActivationMail',
        'admin_homepage' => 'goAdminTop',
        'admin_homepage_customer' => ['GET' => 'goCustomerList', 'POST' => 'goCustomerList'],
        'admin_homepage_nonstock' => ['GET' => 'goProductList', 'POST' => 'goProductList'],
        'admin_homepage_sale' => ['GET' => 'goOrderList', 'POST' => 'goOrderList'],
        'admin_login' => ['GET' => 'goAdminLogin', 'POST' => 'doAdminLogin'],
        'admin_logout' => 'doAdminLogout',
        'admin_order' => 'goOrderList',
        'admin_order_bulk_delete' => ['GET' => 'goOrderList', 'POST' => 'doBulkDeleteOrder'],
        'admin_order_csv_shipping' => ['GET' => 'goImportShippingCsv', 'POST' => 'doImportShippingCsv'],
        'admin_order_edit' => ['GET' => 'goOrder', 'POST' => 'doUpdateOrder'],
        'admin_order_export_order' => 'goExportOrder',
        'admin_order_export_pdf' => 'goExportOrderPdf',
        'admin_order_export_shipping' => 'goExportShipping',
        'admin_order_mail' => ['GET' => 'goOrderMail', 'POST' => 'doSendOrderMail'],
        'admin_order_shipping' => ['GET' => 'goOrderShippingAddress', 'POST' => 'doUpdateOrderShippingAddress'],
        'admin_product' => 'goProductList',
        'admin_product_bulk_product_status' => ['GET' => 'goProductList', 'POST' => 'doBulkUpdateProductStatus'],
        'admin_product_category' => 'goCategoryList',
        'admin_product_category_edit' => ['GET' => 'goCategory', 'POST' => 'doUpdateCategory'],
        'admin_product_class_category' => ['GET' => 'goClassCategoryList', 'POST' => 'doCreateClassCategory'],
        'admin_product_class_category_delete' => ['GET' => 'goClassCategoryList', 'POST' => 'doDeleteClassCategory'],
        'admin_product_class_category_edit' => ['GET' => 'goClassCategory', 'POST' => 'doUpdateClassCategory'],
        'admin_product_class_category_export' => 'goExportClassCategory',
        'admin_product_class_category_sort_no_move' => ['GET' => 'goClassCategoryList', 'POST' => 'doSortNoMove'],
        'admin_product_class_category_visibility' => ['GET' => 'goClassCategoryList', 'POST' => 'doToggleVisible'],
        'admin_product_class_name' => 'goClassNameList',
        'admin_product_class_name_delete' => ['GET' => 'goClassNameList', 'POST' => 'doDeleteClassName'],
        'admin_product_class_name_export' => 'goExportClassName',
        'admin_product_class_name_sort_no_move' => ['GET' => 'goClassNameList', 'POST' => 'doSortNoMove'],
        'admin_product_csv_category' => ['GET' => 'goExportCategory', 'POST' => 'doImportCategoryCsv'],
        'admin_product_csv_class_category' => ['GET' => 'goExportClassCategory', 'POST' => 'doImportClassCategoryCsv'],
        'admin_product_csv_class_name' => ['GET' => 'goExportClassName', 'POST' => 'doImportClassNameCsv'],
        'admin_product_csv_product' => ['GET' => 'goExportProduct', 'POST' => 'doImportProductCsv'],
        'admin_product_export' => 'goExportProduct',
        'admin_product_product_class' => 'goProduct',
        'admin_product_product_copy' => ['GET' => 'goProduct', 'POST' => 'doCopyProduct'],
        'admin_product_product_delete' => ['GET' => 'goProductList', 'POST' => 'doDeleteProduct'],
        'admin_product_product_edit' => ['GET' => 'goProduct', 'POST' => 'doUpdateProduct'],
        'admin_product_product_new' => ['GET' => 'goProduct', 'POST' => 'doCreateProduct'],
        'admin_product_tag' => 'goTagList',
        'admin_product_tag_delete' => ['GET' => 'goTagList', 'POST' => 'doDeleteTag'],
        'admin_product_tag_sort_no_move' => ['GET' => 'goTagList', 'POST' => 'doSortNoMove'],
        'admin_setting_shop' => ['GET' => 'goBaseInfo', 'POST' => 'doUpdateBaseInfo'],
        'admin_setting_shop_calendar' => ['GET' => 'goCalendar', 'POST' => 'doUpdateCalendar'],
        'admin_setting_shop_calendar_delete' => ['GET' => 'goCalendar', 'POST' => 'doDeleteCalendarHoliday'],
        'admin_setting_shop_calendar_new' => ['GET' => 'goCalendar', 'POST' => 'doCreateCalendarHoliday'],
        'admin_setting_shop_csv' => ['GET' => 'goCsv', 'POST' => 'doUpdateCsv'],
        'admin_setting_shop_delivery' => ['GET' => 'goDeliveryList', 'POST' => 'goDeliveryList'],
        'admin_setting_shop_delivery_delete' => ['GET' => 'goDeliveryList', 'POST' => 'doDeleteDelivery'],
        'admin_setting_shop_delivery_edit' => ['GET' => 'goDelivery', 'POST' => 'doUpdateDelivery'],
        'admin_setting_shop_delivery_new' => ['GET' => 'goDelivery', 'POST' => 'doCreateDelivery'],
        'admin_setting_shop_delivery_sort_no_move' => ['GET' => 'goDeliveryList', 'POST' => 'doSortNoMove'],
        'admin_setting_shop_delivery_visibility' => ['GET' => 'goDeliveryList', 'POST' => 'doToggleVisible'],
        'admin_setting_shop_mail' => ['GET' => 'goMailTemplateList', 'POST' => 'doUpdateMailTemplate'],
        'admin_setting_shop_mail_delete' => ['GET' => 'goMailTemplateList', 'POST' => 'doDeleteMailTemplate'],
        'admin_setting_shop_order_status' => ['GET' => 'goOrderStatusList', 'POST' => 'doUpdateOrderStatusList'],
        'admin_setting_shop_payment' => 'goPaymentList',
        'admin_setting_shop_payment_delete' => ['GET' => 'goPaymentList', 'POST' => 'doDeletePayment'],
        'admin_setting_shop_payment_edit' => ['GET' => 'goPayment', 'POST' => 'doUpdatePayment'],
        'admin_setting_shop_payment_new' => ['GET' => 'goPayment', 'POST' => 'doCreatePayment'],
        'admin_setting_shop_payment_sort_no_move' => ['GET' => 'goPaymentList', 'POST' => 'doSortNoMove'],
        'admin_setting_shop_payment_visible' => ['GET' => 'goPaymentList', 'POST' => 'doToggleVisible'],
        'admin_setting_shop_tax' => 'goTaxRuleList',
        'admin_setting_shop_tax_delete' => ['GET' => 'goTaxRuleList', 'POST' => 'doDeleteTaxRule'],
        'admin_setting_shop_tax_new' => 'doCreateTaxRule',
        'admin_setting_shop_tradelaw' => ['GET' => 'goTradeLawList', 'POST' => 'doUpdateTradeLaw'],
        'admin_setting_system_authority' => ['GET' => 'goAuthorityRole', 'POST' => 'doUpdateAuthorityRole'],
        'admin_setting_system_masterdata' => ['GET' => 'goMasterData', 'POST' => 'doSelectMasterData'],
        'admin_setting_system_masterdata_edit' => ['GET' => 'goMasterData', 'POST' => 'doUpdateMasterData'],
        'admin_setting_system_member' => 'goMemberList',
        'admin_setting_system_member_delete' => ['GET' => 'goMemberList', 'POST' => 'doDeleteMember'],
        'admin_setting_system_member_down' => ['GET' => 'goMemberList', 'POST' => 'doSortNoMove'],
        'admin_setting_system_member_edit' => ['GET' => 'goMember', 'POST' => 'doUpdateMember'],
        'admin_setting_system_member_new' => ['GET' => 'goMember', 'POST' => 'doCreateMember'],
        'admin_setting_system_member_up' => ['GET' => 'goMemberList', 'POST' => 'doSortNoMove'],
        'admin_setting_system_security' => ['GET' => 'goSecurity', 'POST' => 'doUpdateSecurity'],
        'admin_setting_system_system_phpinfo' => 'goSystemInfo',
        'admin_shipping_notify_mail' => ['GET' => 'goOrder', 'POST' => 'doSendShippingNotifyMail'],
        'admin_shipping_preview_notify_mail' => 'goOrderMailConfirm',
        'admin_shipping_update_order_status' => ['GET' => 'goOrderList', 'POST' => 'doUpdateOrderStatus'],
        'admin_shipping_update_tracking_number' => ['GET' => 'goOrder', 'POST' => 'doUpdateTrackingNumber'],
        'admin_store_plugin' => 'goPluginList',
        'admin_store_plugin_disable' => ['GET' => 'goPluginList', 'POST' => 'doDisablePlugin'],
        'admin_store_plugin_enable' => ['GET' => 'goPluginList', 'POST' => 'doEnablePlugin'],
        'admin_store_plugin_install' => ['GET' => 'goPluginList', 'POST' => 'doInstallPlugin'],
        'admin_store_plugin_owners_search_page' => ['GET' => 'goPluginList', 'POST' => 'goPluginList'],
        'admin_store_plugin_uninstall' => ['GET' => 'goPluginList', 'POST' => 'doUninstallPlugin'],
        'admin_store_template' => ['GET' => 'goTemplateList', 'POST' => 'doSelectTemplate'],
        'admin_store_template_delete' => ['GET' => 'goTemplateList', 'POST' => 'doDeleteTemplate'],
        'admin_store_template_download' => ['GET' => 'goTemplateList', 'POST' => 'doDownloadTemplate'],
        'admin_store_template_install' => ['GET' => 'goTemplateInstall', 'POST' => 'doInstallTemplate'],
        'admin_two_factor_auth' => ['GET' => 'goTwoFactorAuth', 'POST' => 'doVerifyTwoFactorAuth'],
        'admin_two_factor_auth_set' => ['GET' => 'goTwoFactorAuthSet', 'POST' => 'doSetTwoFactorAuth'],
        'block_cart' => 'goCart',
        'cart' => 'goCart',
        'cart_handle_item' => ['GET' => 'goCart', 'POST' => 'doUpdateCartItemQuantity'],
        'contact' => ['GET' => 'goContactForm', 'POST' => 'doSubmitContact'],
        'contact_complete' => 'goContactComplete',
        'contact_confirm' => 'doSubmitContact',
        'entry' => ['GET' => 'goCustomerRegistration', 'POST' => 'doRegisterCustomer'],
        'entry_activate' => 'doActivateCustomer',
        'entry_complete' => 'goCustomerRegistrationComplete',
        'entry_confirm' => 'goCustomerRegistrationConfirm',
        'forgot' => ['GET' => 'goPasswordResetRequest', 'POST' => 'doRequestPasswordReset'],
        'forgot_complete' => 'goPasswordResetRequestComplete',
        'forgot_reset' => ['GET' => 'goPasswordReset', 'POST' => 'doResetPassword'],
        'help_about' => 'goHelpAbout',
        'help_agreement' => 'goHelpAgreement',
        'help_guide' => 'goHelpGuide',
        'help_privacy' => 'goHelpPrivacy',
        'help_tradelaw' => 'goHelpTradeLaw',
        'homepage' => 'goTop',
        'logout' => 'doLogout',
        'mypage' => 'goMypage',
        'mypage_change' => ['GET' => 'goMypageChange', 'POST' => 'doUpdateCustomer'],
        'mypage_change_complete' => 'goMypageChangeComplete',
        'mypage_delivery' => 'goCustomerAddressList',
        'mypage_delivery_delete' => ['GET' => 'goCustomerAddressList', 'POST' => 'doDeleteCustomerAddress'],
        'mypage_delivery_edit' => ['GET' => 'goCustomerAddress', 'POST' => 'doUpdateCustomerAddress'],
        'mypage_delivery_new' => ['GET' => 'goCustomerAddress', 'POST' => 'doCreateCustomerAddress'],
        'mypage_favorite' => 'goFavoriteList',
        'mypage_favorite_delete' => ['GET' => 'goFavoriteList', 'POST' => 'doRemoveFavorite'],
        'mypage_history' => 'goMypageHistory',
        'mypage_login' => ['GET' => 'goLogin', 'POST' => 'doLogin'],
        'mypage_order' => 'doReorder',
        'mypage_withdraw' => ['GET' => 'goMypageWithdraw', 'POST' => 'doWithdrawCustomer'],
        'mypage_withdraw_confirm' => ['GET' => 'goMypageWithdrawConfirm', 'POST' => 'doWithdrawCustomer'],
        'mypage_withdraw_complete' => 'goMypageWithdrawComplete',
        'cart_buystep' => 'doSelectCartForCheckout',
        'product_add_cart' => 'doAddCartItem',
        'product_add_favorite' => ['GET' => 'goProduct', 'POST' => 'doAddFavorite'],
        'product_delete_favorite' => 'doRemoveFavorite',
        'product_detail' => 'goProduct',
        'product_list' => 'goProductList',
        'shopping' => 'goShopping',
        'shopping_checkout' => 'doCheckout',
        'shopping_customer' => 'doSubmitNonMember',
        'shopping_complete' => 'goShoppingComplete',
        'shopping_confirm' => 'doConfirmOrder',
        'shopping_error' => 'goShoppingError',
        'shopping_login' => 'goShoppingLogin',
        'shopping_nonmember' => ['GET' => 'goShoppingNonMember', 'POST' => 'doSubmitNonMember'],
        'shopping_redirect_to' => 'doShoppingRedirectTo',
        'shopping_shipping' => ['GET' => 'goShoppingShipping', 'POST' => 'doSelectShippingAddress'],
        'shopping_shipping_edit' => ['GET' => 'goShoppingShippingEdit', 'POST' => 'doUpdateShippingAddress'],
        'shopping_shipping_multiple' => ['GET' => 'goShoppingShippingMultiple', 'POST' => 'doSelectShippingAddress'],
        'shopping_shipping_multiple_edit' => ['GET' => 'goShoppingShippingMultiple', 'POST' => 'doUpdateShippingAddress'],
];

$fallbackAlps = static function (string $routeName, string $method, string|null $dispatchMethod): string {
    $subject = (string) preg_replace_callback(
        '/(?:^|_)([a-z])/',
        static fn (array $m): string => ucfirst($m[1]),
        $routeName,
    );
    if ($method === 'GET' || $dispatchMethod === 'get') {
        return 'go' . $subject;
    }

    if ($dispatchMethod === 'delete' || str_ends_with($routeName, '_delete')) {
        return 'doDelete' . $subject;
    }

    if ($dispatchMethod === 'put' || str_contains($routeName, 'update')) {
        return 'doUpdate' . $subject;
    }

    return 'do' . $subject;
};

/**
 * @param list<string> $methods
 * @return array{ids: array<string, string>, explicit: bool}
 */
$alpsFor = static function (string $routeName, array $methods, string|null $dispatchMethod) use ($alpsIds, $fallbackAlps): array {
    if (array_key_exists($routeName, $alpsIds)) {
        $value = $alpsIds[$routeName];
        if (is_array($value)) {
            return ['ids' => $value, 'explicit' => true];
        }

        $ids = [];
        foreach ($methods as $method) {
            $ids[strtoupper($method)] = $value;
        }

        return ['ids' => $ids, 'explicit' => true];
    }

    $ids = [];
    foreach ($methods as $method) {
        $method = strtoupper($method);
        $ids[$method] = $fallbackAlps($routeName, $method, $dispatchMethod);
    }

    return ['ids' => $ids, 'explicit' => false];
};

/** @return array<string, array<string, mixed>> */
$methodMetadataFor = static function (AuraRoute $route): array {
    /** @var mixed $bemart */
    $bemart = $route->extras['bemart'] ?? null;
    if (! is_array($bemart) || ! isset($bemart['methods']) || ! is_array($bemart['methods'])) {
        return [];
    }

    /** @var array<string, array<string, mixed>> */
    return $bemart['methods'];
};

$auraRoute = static function (Map $map, string $name, string $path): AuraRoute {
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
};

/**
 * @param list<string>          $methods
 * @param array<string,string> $paramMap
 * @param array<string,string> $defaults
 * @param array<string,string> $queryParamMap
 */
$route = static function (
    Map $map,
    string $name,
    array $methods,
    string $path,
    string $resource,
    array $paramMap = [],
    string|null $dispatchMethod = null,
    array $defaults = [],
    array $queryParamMap = [],
) use ($auraRoute, $methodMetadataFor, $alpsFor, $fallbackAlps): void {
    $route = $auraRoute($map, $name, $path);
    $existingMethods = $methodMetadataFor($route);
    $newMethods = [];
    $alps = $alpsFor($name, $methods, $dispatchMethod);
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
            'alpsId' => $alps['ids'][$method] ?? $fallbackAlps($name, $method, $dispatchMethod),
            'alpsExplicit' => $alps['explicit'] && array_key_exists($method, $alps['ids']),
        ];
    }

    $route->extras([
        'bemart' => [
            'methods' => $newMethods,
        ],
    ]);
};

/** @param array<string, string> $queryParamMap */
$adminGet = static function (Map $map, string $name, string $resource, array $queryParamMap = [], array $defaults = []) use ($route): void {
    $route(
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
};

/** @param array<string, string> $queryParamMap */
$adminPost = static function (
    Map $map,
    string $name,
    string $resource,
    string|null $dispatchMethod = null,
    array $queryParamMap = [],
    array $defaults = [],
) use ($route): void {
    $route(
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
};

/** @param array<string, string> $queryParamMap */
$adminGetPost = static function (
    Map $map,
    string $name,
    string $resource,
    array $queryParamMap = [],
    array $defaults = [],
) use ($route): void {
    $route(
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
};

$adminAliasRoutes = static function (Map $map) use ($adminGet, $adminPost, $adminGetPost): void {
            // Content / CMS.
            $adminGet($map, 'admin_content_block_new', 'page://self/admin/block/block');
            $adminGet($map, 'admin_content_block_edit', 'page://self/admin/block/block', ['id' => 'blockId']);
            $adminGet($map, 'admin_content_block_delete', 'page://self/admin/block/block-list');
            $adminPost($map, 'admin_content_block_delete', 'page://self/admin/block/block', 'delete', ['id' => 'blockId']);
            $adminGet($map, 'admin_content_cache', 'page://self/admin/content/cache');
            $adminPost($map, 'admin_content_cache', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_content_cache']);
            $adminGet($map, 'admin_content_css', 'page://self/admin/content/css');
            $adminPost($map, 'admin_content_css', 'page://self/admin/content/css');
            $adminGet($map, 'admin_content_js', 'page://self/admin/content/js');
            $adminPost($map, 'admin_content_js', 'page://self/admin/content/js');
            $adminGet($map, 'admin_content_layout_new', 'page://self/admin/layout/layout');
            $adminGet($map, 'admin_content_layout_edit', 'page://self/admin/layout/layout', ['id' => 'layoutId']);
            $adminGet($map, 'admin_content_maintenance', 'page://self/admin/content/maintenance');
            $adminPost($map, 'admin_content_maintenance', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_content_maintenance']);
            $adminGet($map, 'admin_content_news_new', 'page://self/admin/news/news');
            $adminGet($map, 'admin_content_news_edit', 'page://self/admin/news/news', ['id' => 'newsId']);
            $adminGet($map, 'admin_content_news_delete', 'page://self/admin/news/news-list');
            $adminPost($map, 'admin_content_news_delete', 'page://self/admin/news/news', 'delete', ['id' => 'newsId']);
            $adminGet($map, 'admin_content_page_new', 'page://self/admin/page/page');
            $adminGet($map, 'admin_content_page_edit', 'page://self/admin/page/page', ['id' => 'pageId']);
            $adminGet($map, 'admin_content_page_delete', 'page://self/admin/page/page-list');
            $adminPost($map, 'admin_content_page_delete', 'page://self/admin/page/page', 'delete', ['id' => 'pageId']);

            // Customer.
            $adminGet($map, 'admin_customer_edit', 'page://self/admin/customer', ['id' => 'customerId']);
            $adminGet($map, 'admin_customer_delete', 'page://self/admin/customer-list');
            $adminPost($map, 'admin_customer_delete', 'page://self/admin/delete-customer', 'post', ['id' => 'customerId']);
            $adminGet($map, 'admin_customer_export', 'page://self/admin/customer-csv');
            $adminPost($map, 'admin_customer_export', 'page://self/admin/customer-csv', 'get');
            $adminGet($map, 'admin_customer_delivery_new', 'page://self/admin/customer-delivery-edit', ['id' => 'customerId']);

            // Dashboard drill-downs.
            $adminGet($map, 'admin_homepage_customer', 'page://self/admin/customer-list');
            $adminGet($map, 'admin_homepage_nonstock', 'page://self/admin/product-list');
            $adminGet($map, 'admin_homepage_sale', 'page://self/admin/order-list');

            // Order.
            $adminGet($map, 'admin_order_edit', 'page://self/admin/order/edit', ['id' => 'orderNo']);
            $adminGet($map, 'admin_order_bulk_delete', 'page://self/admin/order-list');
            $adminPost($map, 'admin_order_bulk_delete', 'page://self/admin/order/bulk-delete', 'post', ['ids' => 'orderNos']);
            $adminGet($map, 'admin_order_csv_shipping', 'page://self/admin/order/import-shipping');
            $adminPost($map, 'admin_order_csv_shipping', 'page://self/admin/order/import-shipping', 'post', ['import_file' => 'csv'], ['csv' => '']);
            $adminGet($map, 'admin_order_export_order', 'page://self/admin/order/export-order');
            $adminPost($map, 'admin_order_export_order', 'page://self/admin/order/export-order', 'get');
            $adminGet($map, 'admin_order_export_pdf', 'page://self/admin/order/export-order-pdf', ['ids' => 'orderNos']);
            $adminPost($map, 'admin_order_export_pdf', 'page://self/admin/order/export-order-pdf', 'get', ['ids' => 'orderNos']);
            $adminGet($map, 'admin_order_export_shipping', 'page://self/admin/order/export-shipping');
            $adminPost($map, 'admin_order_export_shipping', 'page://self/admin/order/export-shipping', 'get');
            $adminGetPost($map, 'admin_order_mail', 'page://self/admin/order/send-mail', ['id' => 'orderNo']);
            $adminGetPost($map, 'admin_order_shipping', 'page://self/admin/order/shipping-address', ['id' => 'orderNo']);
            $adminGet($map, 'admin_shipping_notify_mail', 'page://self/admin/order/edit', ['id' => 'orderNo']);
            $adminPost($map, 'admin_shipping_notify_mail', 'page://self/admin/order/shipping-notify-mail', 'post', ['id' => 'orderNo']);
            $adminGet($map, 'admin_shipping_preview_notify_mail', 'page://self/admin/order/mail-confirm', ['id' => 'orderNo']);
            $adminPost($map, 'admin_shipping_preview_notify_mail', 'page://self/admin/order/mail-confirm', 'get', ['id' => 'orderNo']);
            $adminGet($map, 'admin_shipping_update_order_status', 'page://self/admin/order-list');
            $adminPost($map, 'admin_shipping_update_order_status', 'page://self/admin/order', 'put', ['id' => 'orderNo']);
            $adminGet($map, 'admin_shipping_update_tracking_number', 'page://self/admin/order/edit', ['id' => 'orderNo']);
            $adminPost($map, 'admin_shipping_update_tracking_number', 'page://self/admin/order/tracking-number', 'put', ['id' => 'orderNo']);

            // Product / catalogue.
            $adminGet($map, 'admin_product_product_new', 'page://self/admin/product-new');
            $adminPost($map, 'admin_product_product_new', 'page://self/admin/product');
            $adminGet($map, 'admin_product_product_edit', 'page://self/admin/product/edit', ['id' => 'productCode']);
            $adminPost($map, 'admin_product_product_edit', 'page://self/admin/product', 'put', ['id' => 'productCode']);
            $adminGet($map, 'admin_product_product_delete', 'page://self/admin/product-list');
            $adminPost($map, 'admin_product_product_delete', 'page://self/admin/product', 'delete', ['id' => 'productCode']);
            $adminGet($map, 'admin_product_product_copy', 'page://self/admin/product/edit', ['id' => 'productCode']);
            $adminPost($map, 'admin_product_product_copy', 'page://self/admin/product-copy', 'post', ['id' => 'productCode']);
            $adminGet($map, 'admin_product_product_class', 'page://self/admin/product/product-class', ['id' => 'productCode']);
            $adminPost($map, 'admin_product_product_class', 'page://self/admin/product/product-class', 'get', ['id' => 'productCode']);
            $adminGet($map, 'admin_product_bulk_product_status', 'page://self/admin/product-list');
            $adminPost($map, 'admin_product_bulk_product_status', 'page://self/admin/product-bulk-status');
            $adminGet($map, 'admin_product_export', 'page://self/admin/product-csv');
            $adminPost($map, 'admin_product_export', 'page://self/admin/product-csv', 'get');
            $adminGet($map, 'admin_product_csv_product', 'page://self/admin/product-csv');
            $adminPost($map, 'admin_product_csv_product', 'page://self/admin/product-csv', 'get');
            $adminGet($map, 'admin_product_csv_category', 'page://self/admin/category/csv');
            $adminPost($map, 'admin_product_csv_category', 'page://self/admin/category/csv', 'post', ['import_file' => 'csv'], ['csv' => '']);
            $adminGetPost($map, 'admin_product_csv_class_name', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']);
            $adminGetPost($map, 'admin_product_csv_class_category', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']);
            $adminGet($map, 'admin_product_category_edit', 'page://self/admin/category/category', ['id' => 'categoryId']);
            $adminGetPost($map, 'admin_product_class_category', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId']);
            $adminGet($map, 'admin_product_class_category_edit', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId', 'id' => 'classCategoryId']);
            $adminGet($map, 'admin_product_class_category_delete', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId']);
            $adminPost($map, 'admin_product_class_category_delete', 'page://self/admin/class-category/class-category', 'delete', ['id' => 'classCategoryId']);
            $adminGetPost($map, 'admin_product_class_category_export', 'page://self/admin/action-redirect', ['class_name_id' => 'classNameId'], ['returnTo' => '/admin/product/class_name']);
            $adminGet($map, 'admin_product_class_category_sort_no_move', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId']);
            $adminPost($map, 'admin_product_class_category_sort_no_move', 'page://self/admin/sort-no-move', 'put', ['id' => 'rowId'], ['masterType' => 'classCategory']);
            $adminGet($map, 'admin_product_class_category_visibility', 'page://self/admin/class-category/class-category-list', ['class_name_id' => 'classNameId']);
            $adminPost($map, 'admin_product_class_category_visibility', 'page://self/admin/toggle-visible', 'put', ['id' => 'rowId'], ['masterType' => 'classCategory']);
            $adminGet($map, 'admin_product_class_name_delete', 'page://self/admin/class-name/class-name-list');
            $adminPost($map, 'admin_product_class_name_delete', 'page://self/admin/class-name/class-name', 'delete', ['id' => 'classNameId']);
            $adminGetPost($map, 'admin_product_class_name_export', 'page://self/admin/action-redirect', [], ['returnTo' => '/admin/product/class_name']);
            $adminGet($map, 'admin_product_class_name_sort_no_move', 'page://self/admin/class-name/class-name-list');
            $adminPost($map, 'admin_product_class_name_sort_no_move', 'page://self/admin/sort-no-move', 'put', ['id' => 'rowId'], ['masterType' => 'className']);
            $adminGet($map, 'admin_product_tag_delete', 'page://self/admin/tag/tag-list');
            $adminPost($map, 'admin_product_tag_delete', 'page://self/admin/tag/tag', 'delete', ['id' => 'tagId']);
            $adminGet($map, 'admin_product_tag_sort_no_move', 'page://self/admin/tag/tag-list');
            $adminPost($map, 'admin_product_tag_sort_no_move', 'page://self/admin/sort-no-move', 'put', ['id' => 'rowId'], ['masterType' => 'tag']);

            // Shop settings.
            $adminGetPost($map, 'admin_setting_shop', 'page://self/admin/base-info');
            $adminGet($map, 'admin_setting_shop_calendar', 'page://self/admin/calendar');
            $adminPost($map, 'admin_setting_shop_calendar', 'page://self/admin/calendar', 'post', [], ['operation' => 'update']);
            $adminGet($map, 'admin_setting_shop_calendar_new', 'page://self/admin/calendar');
            $adminPost($map, 'admin_setting_shop_calendar_new', 'page://self/admin/calendar', 'post', [], ['operation' => 'create']);
            $adminGet($map, 'admin_setting_shop_calendar_delete', 'page://self/admin/calendar');
            $adminPost($map, 'admin_setting_shop_calendar_delete', 'page://self/admin/calendar', 'delete', ['id' => 'calendarId']);
            $adminGetPost($map, 'admin_setting_shop_csv', 'page://self/admin/csv-config');
            $adminGet($map, 'admin_setting_shop_delivery_new', 'page://self/admin/delivery/delivery');
            $adminGet($map, 'admin_setting_shop_delivery_edit', 'page://self/admin/delivery/delivery', ['id' => 'deliveryId']);
            $adminGet($map, 'admin_setting_shop_delivery_delete', 'page://self/admin/delivery/delivery-list');
            $adminPost($map, 'admin_setting_shop_delivery_delete', 'page://self/admin/delivery/delivery', 'delete', ['id' => 'deliveryId']);
            $adminGet($map, 'admin_setting_shop_delivery_sort_no_move', 'page://self/admin/delivery/delivery-list');
            $adminPost($map, 'admin_setting_shop_delivery_sort_no_move', 'page://self/admin/sort-no-move', 'put', ['id' => 'rowId'], ['masterType' => 'delivery']);
            $adminGet($map, 'admin_setting_shop_delivery_visibility', 'page://self/admin/delivery/delivery-list');
            $adminPost($map, 'admin_setting_shop_delivery_visibility', 'page://self/admin/toggle-visible', 'put', ['id' => 'rowId'], ['masterType' => 'delivery']);
            $adminGetPost($map, 'admin_setting_shop_mail', 'page://self/admin/mail-template');
            $adminGet($map, 'admin_setting_shop_mail_delete', 'page://self/admin/mail-template');
            $adminPost($map, 'admin_setting_shop_mail_delete', 'page://self/admin/mail-template', 'delete', ['id' => 'mailTemplateId']);
            $adminGet($map, 'admin_setting_shop_order_status', 'page://self/admin/order-status');
            $adminPost($map, 'admin_setting_shop_order_status', 'page://self/admin/order-status', 'put');
            $adminGet($map, 'admin_setting_shop_payment_new', 'page://self/admin/payment/payment');
            $adminGet($map, 'admin_setting_shop_payment_edit', 'page://self/admin/payment/payment', ['id' => 'paymentId']);
            $adminGet($map, 'admin_setting_shop_payment_delete', 'page://self/admin/payment/payment-list');
            $adminPost($map, 'admin_setting_shop_payment_delete', 'page://self/admin/payment/payment', 'delete', ['id' => 'paymentId']);
            $adminGet($map, 'admin_setting_shop_payment_sort_no_move', 'page://self/admin/payment/payment-list');
            $adminPost($map, 'admin_setting_shop_payment_sort_no_move', 'page://self/admin/sort-no-move', 'put', ['id' => 'rowId'], ['masterType' => 'payment']);
            $adminGet($map, 'admin_setting_shop_payment_visible', 'page://self/admin/payment/payment-list');
            $adminPost($map, 'admin_setting_shop_payment_visible', 'page://self/admin/toggle-visible', 'put', ['id' => 'rowId'], ['masterType' => 'payment']);
            $adminPost($map, 'admin_setting_shop_tax_new', 'page://self/admin/tax-rule/tax-rule-list', 'post', ['tax_rate' => 'taxRate', 'apply_date' => 'applyDate', 'rounding_type' => 'roundingType']);
            $adminGet($map, 'admin_setting_shop_tax_delete', 'page://self/admin/tax-rule/tax-rule-list');
            $adminPost($map, 'admin_setting_shop_tax_delete', 'page://self/admin/tax-rule/tax-rule', 'delete', ['id' => 'taxRuleId']);
            $adminGet($map, 'admin_setting_shop_tradelaw', 'page://self/admin/trade-law');
            $adminPost($map, 'admin_setting_shop_tradelaw', 'page://self/admin/trade-law', 'post');

            // System settings.
            $adminGetPost($map, 'admin_setting_system_authority', 'page://self/admin/authority-role');
            $adminGet($map, 'admin_setting_system_masterdata', 'page://self/admin/master-data');
            $adminPost($map, 'admin_setting_system_masterdata', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_system_masterdata']);
            $adminGet($map, 'admin_setting_system_masterdata_edit', 'page://self/admin/master-data');
            $adminPost($map, 'admin_setting_system_masterdata_edit', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_system_masterdata']);
            $adminGet($map, 'admin_setting_system_member_new', 'page://self/admin/member');
            $adminPost($map, 'admin_setting_system_member_new', 'page://self/admin/member');
            $adminGet($map, 'admin_setting_system_member_edit', 'page://self/admin/member', ['id' => 'loginId']);
            $adminPost($map, 'admin_setting_system_member_edit', 'page://self/admin/member', 'put', ['id' => 'loginId']);
            $adminGet($map, 'admin_setting_system_member_delete', 'page://self/admin/member-list');
            $adminPost($map, 'admin_setting_system_member_delete', 'page://self/admin/member', 'delete', ['id' => 'loginId']);
            $adminGet($map, 'admin_setting_system_member_up', 'page://self/admin/member-list', ['id' => 'loginId']);
            $adminPost($map, 'admin_setting_system_member_up', 'page://self/admin/sort-no-move', 'put', ['id' => 'rowId'], ['masterType' => 'member']);
            $adminGet($map, 'admin_setting_system_member_down', 'page://self/admin/member-list', ['id' => 'loginId']);
            $adminPost($map, 'admin_setting_system_member_down', 'page://self/admin/sort-no-move', 'put', ['id' => 'rowId'], ['masterType' => 'member']);
            $adminGet($map, 'admin_setting_system_security', 'page://self/admin/security');
            $adminPost($map, 'admin_setting_system_security', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_setting_system_security']);
            $adminGet($map, 'admin_setting_system_system_phpinfo', 'page://self/admin/system');
            $adminPost($map, 'admin_setting_system_system_phpinfo', 'page://self/admin/system', 'get');

            // Store / plugin / template.
            $adminGet($map, 'admin_store_plugin_owners_search_page', 'page://self/admin/plugin-list');
            $adminGet($map, 'admin_store_plugin_enable', 'page://self/admin/plugin-list');
            $adminPost($map, 'admin_store_plugin_enable', 'page://self/admin/plugin-enable', 'post', ['code' => 'pluginCode']);
            $adminGet($map, 'admin_store_plugin_disable', 'page://self/admin/plugin-list');
            $adminPost($map, 'admin_store_plugin_disable', 'page://self/admin/plugin-disable', 'post', ['code' => 'pluginCode']);
            $adminGet($map, 'admin_store_plugin_install', 'page://self/admin/plugin-list');
            $adminPost($map, 'admin_store_plugin_install', 'page://self/admin/plugin-list', 'post', ['code' => 'pluginCode', 'version' => 'pluginVersion']);
            $adminGet($map, 'admin_store_plugin_uninstall', 'page://self/admin/plugin-list');
            $adminPost($map, 'admin_store_plugin_uninstall', 'page://self/admin/plugin', 'delete', ['code' => 'pluginCode']);
            $adminGet($map, 'admin_store_template', 'page://self/admin/template/template-list');
            $adminPost($map, 'admin_store_template', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_store_template']);
            $adminGet($map, 'admin_store_template_install', 'page://self/admin/template/template-add');
            $adminPost($map, 'admin_store_template_install', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_store_template']);
            $adminGet($map, 'admin_store_template_download', 'page://self/admin/template/template-list', ['id' => 'templateId']);
            $adminPost($map, 'admin_store_template_download', 'page://self/admin/action-redirect', 'post', ['id' => 'templateId'], ['returnTo' => '/admin_store_template']);
            $adminGet($map, 'admin_store_template_delete', 'page://self/admin/template/template-list');
            $adminPost($map, 'admin_store_template_delete', 'page://self/admin/action-redirect', 'post', ['id' => 'templateId'], ['returnTo' => '/admin_store_template']);
            $adminGet($map, 'admin_two_factor_auth', 'page://self/admin/two-factor-auth');
            $adminPost($map, 'admin_two_factor_auth', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_two_factor_auth']);
            $adminGet($map, 'admin_two_factor_auth_set', 'page://self/admin/two-factor-auth-set');
            $adminPost($map, 'admin_two_factor_auth_set', 'page://self/admin/action-redirect', 'post', [], ['returnTo' => '/admin_two_factor_auth']);

};

return static function (Map $map) use ($route, $adminAliasRoutes): null {
            // ---- Storefront: top + catalogue ----
            $route($map, 'homepage', ['GET'], '/', 'page://self/');
            $route($map, 'block_cart', ['GET'], '/block/cart', 'page://self/cart');
            $route($map, 'product_list', ['GET'], '/products/list', 'page://self/products');
            $route($map,
                'product_detail',
                ['GET'],
                '/products/detail/{id}',
                'page://self/product',
                ['id' => 'productCode'],
            );

            // ---- Storefront: cart ----
            // EC-CUBE `cart` is GET-only; the BeMart Cart resource serves GET.
            $route($map, 'cart', ['GET'], '/cart', 'page://self/cart');
            // `product_add_cart` POSTs the add-to-cart form. EC-CUBE keys it
            // by the product id in the path; the Cart/Item resource's onPost
            // takes `$productCode`, so the path id renames to `productCode`.
            $route($map,
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
            $route($map, 'cart_handle_item', ['GET'], '/cart/item', 'page://self/cart');
            $route($map, 'cart_handle_item', ['POST'], '/cart/item', 'page://self/cart/item');

            // ---- Storefront: contact ----
            // `contact` serves the form (GET) and the doSubmitContact
            // POST. BeMart's Contact resource collapses EC-CUBE's
            // confirm/complete `mode` branching into a single onPost
            // (see Contact::onPost) — the form posts straight here and
            // the resource redirects to `/contact/complete` on success.
            $route($map, 'contact', ['GET', 'POST'], '/contact', 'page://self/contact');
            $route($map, 'contact_confirm', ['POST'], '/contact/confirm', 'page://self/contact/confirm', [], 'get');
            $route($map, 'contact_complete', ['GET'], '/contact/complete', 'page://self/contact/complete');

            // ---- Storefront: customer registration ----
            $route($map, 'entry', ['GET', 'POST'], '/entry', 'page://self/entry');
            $route($map, 'entry_confirm', ['GET', 'POST'], '/entry/confirm', 'page://self/entry/confirm', [], 'get');
            $route($map, 'entry_complete', ['GET'], '/entry/complete', 'page://self/entry/complete');
            $route($map,
                'entry_activate',
                ['GET', 'POST'],
                '/entry/activate/{secret_key}',
                'page://self/entry/activate',
                ['secret_key' => 'secretKey'],
            );

            // ---- Storefront: authentication ----
            $route($map, 'mypage_login', ['GET', 'POST'], '/mypage/login', 'page://self/login');
            $route($map, 'logout', ['POST'], '/logout', 'page://self/logout');

            // ---- Storefront: password reset ----
            $route($map, 'forgot', ['GET', 'POST'], '/forgot', 'page://self/forgot-password');
            $route($map, 'forgot_complete', ['GET'], '/forgot/complete', 'page://self/forgot-complete');
            $route($map,
                'forgot_reset',
                ['GET', 'POST'],
                '/forgot/reset/{reset_key}',
                'page://self/reset',
                ['reset_key' => 'resetKey'],
            );

            // ---- Storefront: mypage ----
            $route($map, 'mypage', ['GET'], '/mypage', 'page://self/mypage');
            $route($map, 'mypage_change', ['GET', 'POST'], '/mypage/change', 'page://self/mypage/change');
            $route($map,
                'mypage_change_complete',
                ['GET'],
                '/mypage/change_complete',
                'page://self/mypage/change-complete',
            );
            $route($map, 'mypage_delivery', ['GET'], '/mypage/delivery', 'page://self/mypage/address-list');
            $route($map, 'mypage_delivery_new', ['GET'], '/mypage/delivery/new', 'page://self/mypage/address');
            $route($map, 'mypage_delivery_new', ['POST'], '/mypage/delivery/new', 'page://self/mypage/address-list');
            $route($map,
                'mypage_delivery_edit',
                ['GET'],
                '/mypage/delivery/{id}/edit',
                'page://self/mypage/address',
                ['id' => 'addressId'],
            );
            $route($map,
                'mypage_delivery_edit',
                ['POST'],
                '/mypage/delivery/{id}/edit',
                'page://self/mypage/address',
                ['id' => 'addressId'],
                'put',
            );
            $route($map, 'mypage_delivery_delete', ['GET'], '/mypage/delivery/delete', 'page://self/mypage/address-list');
            $route($map, 'mypage_delivery_delete', ['POST'], '/mypage/delivery/delete', 'page://self/mypage/address', [], 'delete', [], ['id' => 'addressId']);
            $route($map, 'mypage_favorite', ['GET'], '/mypage/favorite', 'page://self/mypage/favorite-list');
            $route($map, 'mypage_favorite_delete', ['GET'], '/mypage/favorite/delete', 'page://self/mypage/favorite-list');
            $route($map, 'mypage_favorite_delete', ['POST'], '/mypage/favorite/delete', 'page://self/mypage/favorite', [], 'delete', [], ['id' => 'productCode']);
            $route($map,
                'mypage_history',
                ['GET'],
                '/mypage/history/{order_no}',
                'page://self/mypage/history',
                ['order_no' => 'orderNo'],
            );
            $route($map, 'mypage_withdraw', ['GET', 'POST'], '/mypage/withdraw', 'page://self/mypage/withdraw');
            $route($map, 'mypage_withdraw_confirm', ['GET'], '/mypage/withdraw', 'page://self/mypage/withdraw-confirm');
            $route($map,
                'mypage_withdraw_complete',
                ['GET'],
                '/mypage/withdraw_complete',
                'page://self/mypage/withdraw-complete',
            );

            // ---- Storefront: shopping (checkout flow) ----
            $route($map, 'shopping', ['GET'], '/shopping', 'page://self/shopping');
            $route($map, 'shopping_shipping', ['GET'], '/shopping/shipping', 'page://self/shopping/shipping');
            $route($map, 'shopping_shipping', ['POST'], '/shopping/shipping', 'page://self/shopping/shipping', [], null, [], ['address' => 'shippingAddressId']);
            $route($map, 'shopping_shipping_edit', ['GET'], '/shopping/shipping/edit', 'page://self/shopping/shipping-edit');
            $route($map, 'shopping_shipping_edit', ['POST'], '/shopping/shipping/edit', 'page://self/shopping/shipping-edit');
            $route($map, 'shopping_shipping_multiple', ['GET'], '/shopping/shipping/multiple', 'page://self/shopping/shipping-multiple');
            $route($map, 'shopping_shipping_multiple', ['POST'], '/shopping/shipping/multiple', 'page://self/shopping/shipping-multiple');
            $route($map, 'shopping_shipping_multiple_edit', ['GET'], '/shopping/shipping/multiple/edit', 'page://self/shopping/shipping-multiple-edit');
            $route($map, 'shopping_shipping_multiple_edit', ['POST'], '/shopping/shipping/multiple/edit', 'page://self/shopping/shipping-multiple-edit');
            $route($map, 'shopping_login', ['GET'], '/shopping/login', 'page://self/shopping/login');
            $route($map, 'shopping_nonmember', ['GET', 'POST'], '/shopping/nonmember', 'page://self/shopping/non-member');
            $route($map, 'shopping_confirm', ['POST'], '/shopping/confirm', 'page://self/shopping/confirm', [], 'get');
            $route($map, 'shopping_checkout', ['POST'], '/shopping/checkout', 'page://self/shopping/checkout');
            $route($map, 'shopping_complete', ['GET'], '/shopping/complete', 'page://self/shopping/complete');
            $route($map, 'shopping_error', ['GET'], '/shopping/error', 'page://self/shopping/error');

            // ---- Storefront: static help pages ----
            $route($map, 'help_about', ['GET'], '/help/about', 'page://self/help/about');
            $route($map, 'help_guide', ['GET'], '/guide', 'page://self/help/guide');
            $route($map, 'help_agreement', ['GET'], '/help/agreement', 'page://self/help/agreement');
            $route($map, 'help_privacy', ['GET'], '/help/privacy', 'page://self/help/privacy');
            $route($map, 'help_tradelaw', ['GET'], '/help/tradelaw', 'page://self/help/trade-law');

            // ---- Admin: dashboard + auth ----
            $route($map, 'admin_login', ['GET', 'POST'], '/admin/login', 'page://self/admin/login');
            // The dashboard resource is `Resource\Page\Admin\Index`; its
            // BEAR URI is `page://self/admin/index` (a bare
            // `page://self/admin` resolves to a non-existent `Page\Admin`
            // class — Unbound). `/admin` is the EC-CUBE `admin_homepage`
            // path.
            $route($map, 'admin_homepage', ['GET'], '/admin', 'page://self/admin/index');
            $route($map, 'admin_logout', ['POST'], '/admin/logout', 'page://self/admin/logout');
            $route($map,
                'admin_change_password',
                ['GET'],
                '/admin/change_password',
                'page://self/admin/change-password',
            );
            $route($map,
                'admin_change_password',
                ['POST'],
                '/admin/change_password',
                'page://self/admin/action-redirect',
                [],
                null,
                ['returnTo' => '/admin/change_password'],
            );

            // ---- Admin: catalogue ----
            $route($map, 'admin_product', ['GET', 'POST'], '/admin/product', 'page://self/admin/product-list', [], 'get');
            $route($map, 'admin_product_tag', ['GET', 'POST'], '/admin/product/tag', 'page://self/admin/tag/tag-list', [], 'get');
            $route($map,
                'admin_product_class_name',
                ['GET', 'POST'],
                '/admin/product/class_name',
                'page://self/admin/class-name/class-name-list',
                [],
                'get',
            );
            $route($map,
                'admin_product_category',
                ['GET', 'POST'],
                '/admin/product/category',
                'page://self/admin/category/category-list',
                [],
                'get',
            );

            // ---- Admin: orders + customers ----
            $route($map, 'admin_order', ['GET', 'POST'], '/admin/order', 'page://self/admin/order-list', [], 'get');
            $route($map, 'admin_customer', ['GET', 'POST'], '/admin/customer', 'page://self/admin/customer-list', [], 'get');
            // `admin_customer_resend` POSTs the "resend the email-verification
            // mail to a 仮会員" action from a customer-list row. EC-CUBE keys
            // its route by the customer id in the path; the BeMart Be Input
            // takes the customer's `email`, so the action POSTs the email in
            // the body and the path stays parameterless.
            $route($map,
                'admin_customer_resend',
                ['POST'],
                '/admin/customer/resend-activation-mail',
                'page://self/admin/customer/resend-activation-mail',
            );

            // ---- Admin: content (CMS) ----
            $route($map,
                'admin_content_news',
                ['GET'],
                '/admin/content/news',
                'page://self/admin/news/news-list',
            );
            $route($map,
                'admin_content_page',
                ['GET'],
                '/admin/content/page',
                'page://self/admin/page/page-list',
            );
            $route($map,
                'admin_content_layout',
                ['GET'],
                '/admin/content/layout',
                'page://self/admin/layout/layout-list',
            );
            $route($map,
                'admin_content_block',
                ['GET'],
                '/admin/content/block',
                'page://self/admin/block/block-list',
            );

            // ---- Admin: shop + system settings ----
            $route($map,
                'admin_setting_shop_payment',
                ['GET'],
                '/admin/setting/shop/payment',
                'page://self/admin/payment/payment-list',
            );
            $route($map,
                'admin_setting_shop_delivery',
                ['GET'],
                '/admin/setting/shop/delivery',
                'page://self/admin/delivery/delivery-list',
            );
            $route($map,
                'admin_setting_shop_tax',
                ['GET', 'POST'],
                '/admin/setting/shop/tax',
                'page://self/admin/tax-rule/tax-rule-list',
                [],
                'get',
            );
            $route($map,
                'admin_setting_system_member',
                ['GET', 'POST'],
                '/admin/setting/system/member',
                'page://self/admin/member-list',
                [],
                'get',
            );
            $adminAliasRoutes($map);



            return null;
};
