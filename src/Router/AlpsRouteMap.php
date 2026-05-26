<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Router;

use function array_key_exists;
use function preg_replace_callback;
use function str_contains;
use function str_ends_with;
use function strtoupper;
use function ucfirst;

/**
 * Maps public HTML routes to ALPS transition descriptors.
 *
 * ALPS remains the semantic source of truth: RouteTable owns URL/method dispatch,
 * while this map names the user-visible state or transition behind each route.
 *
 * @psalm-type MethodAlpsMap = array<string, non-empty-string>
 */
final class AlpsRouteMap
{
    /** @var array<non-empty-string, non-empty-string|MethodAlpsMap> */
    private const IDS = [
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

    /** @return non-empty-string|MethodAlpsMap */
    public static function for(string $routeName, array $methods, string|null $dispatchMethod = null): string|array
    {
        if (array_key_exists($routeName, self::IDS)) {
            return self::IDS[$routeName];
        }

        $ids = [];
        foreach ($methods as $method) {
            $method = strtoupper((string) $method);
            $ids[$method] = self::fallback($routeName, $method, $dispatchMethod);
        }

        return $ids;
    }

    public static function has(string $routeName): bool
    {
        return array_key_exists($routeName, self::IDS);
    }

    /** @return non-empty-string */
    private static function fallback(string $routeName, string $method, string|null $dispatchMethod): string
    {
        $subject = (string) preg_replace_callback('/(?:^|_)([a-z])/', static fn (array $m): string => ucfirst($m[1]), $routeName);
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
    }
}
