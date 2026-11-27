<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\LinkException;
use BEAR\Resource\Exception\LinkRelException;
use BEAR\Resource\Exception\ParameterException;
use MyVendor\BeMart\Tests\Fake\Csrf\FakeCsrfToken;

use function dirname;
use function http_build_query;
use function uri_template;

/**
 * Stories a client follows from one URI, each named by what it shows.
 *
 * Each URI is a URI template expanded with the values of its story. A link whose query holds a list writes that query
 * with http_build_query(), the form LinkType::listFromUri() reads.
 *
 * A row is the URI, then the code, path and body subset of the response the story ends on, then the `flow-*` tag of
 * alps.json the story walks (a list when it walks several).
 *
 * @psalm-type StoryRow = array{0: string, 1: int, 2: string, 3: array<string, mixed>, 4?: string|list<string>}
 */
final class LinkStories
{
    private const string MEMBER_ADDRESS = 'addr00000000000000000000000000a1';
    private const string PAST_ORDER = 'past0000000000000000000000000001';
    /** The order the Fake shopping fixtures hold for the checkout of StoryModule::MEMBER_ID */
    private const string PRE_ORDER = 'aceface0000000000000000000000000000a11ce';

    /**
     * @return array<string, StoryRow>
     */
    public static function followable(): array
    {
        return self::forGuests() + self::forAdmins();
    }

    /**
     * Stories a guest follows. Same row shape as followable().
     *
     * @return array<string, StoryRow>
     */
    public static function forGuests(): array
    {
        $inquiry = 'page://self/#link=self:goContactForm,self:doSubmitContact{?form*}';
        $item = self::form(['productCode' => 'sample-001', 'quantity' => '1']);

        return self::guest($inquiry, $item) + self::guestAccountAndHelp() + self::guestPurchase();
    }

    /**
     * @param array<string, string> $item
     *
     * @return array<string, StoryRow>
     */
    private static function guest(string $inquiry, array $item): array
    {
        return [
            'inquiry is answered with a ticket' => [
                uri_template($inquiry, ['form' => self::form(self::inquiry())]),
                Code::OK,
                '/contact/complete',
                [],
                'flow-customer-inquiry',
            ],
            'rejected inquiry stops the chain' => [
                uri_template($inquiry . ',self:goTop', ['form' => self::form(['contactEmail' => 'not-an-email', 'mode' => 'contact'] + self::inquiry())]),
                Code::BAD_REQUEST,
                '/contact',
                [],
                ['flow-customer-inquiry', 'flow-customer-purchase'],
            ],
            'guest adds a cart item and is sent to checkout as a non-member' => [
                uri_template('page://self/cart#link=self:doAddCartItem{?item*},self:goCheckoutEntry,self:goShoppingNonMember', ['item' => $item]),
                Code::OK,
                '/shopping/non-member',
                [],
                'flow-customer-purchase',
            ],
            'guest finds a product in the list and puts it in the cart' => [
                uri_template(
                    'page://self/#link=self:goProductList{?nameKeyword},self:goProduct{?productCode},self:doAddCartItem{?item*}',
                    ['nameKeyword' => 'sample', 'productCode' => 'sample-001', 'item' => $item],
                ),
                Code::OK,
                '/cart',
                [],
                'flow-customer-purchase',
            ],
        ];
    }

    /**
     * flow-help, flow-register, flow-account (the part a guest reaches)
     *
     * @return array<string, StoryRow>
     */
    private static function guestAccountAndHelp(): array
    {
        $help = static fn (string $rel): string => 'self:' . $rel . ',self:goTop,';

        return [
            'every help page leads back to the top' => [
                'page://self/#link=' . $help('goHelpAbout') . $help('goHelpGuide') . $help('goHelpAgreement') . $help('goHelpPrivacy') . 'self:goHelpTradeLaw',
                Code::OK,
                '/help/trade-law',
                [],
            ],
            'registration confirmation leads back to the top and on to the login' => [
                'page://self/entry#link=self:goCustomerRegistrationConfirm,self:goTop,self:goLogin',
                Code::OK,
                '/login',
                [],
                ['flow-customer-account-maintenance', 'flow-customer-purchase', 'flow-customer-registration'],
            ],
            'login page leads to the registration form' => [
                'page://self/login#link=self:goCustomerRegistration',
                Code::OK,
                '/entry',
                [],
                'flow-customer-registration',
            ],
            'password reset is requested from the login page' => [
                uri_template('page://self/login#link=self:doRequestPasswordReset{?form*},self:goLogin', ['form' => self::form(['email' => 'alice@example.com'])]),
                Code::OK,
                '/login',
                [],
                'flow-account',
            ],
        ];
    }

    /**
     * flow-browse, flow-purchase (the part a guest reaches)
     *
     * @return array<string, StoryRow>
     */
    private static function guestPurchase(): array
    {
        return [
            'a product page leads back to the list and on to the cart' => [
                uri_template('page://self/products#link=self:goProduct{?productCode},self:goProductList,self:goCart', ['productCode' => 'sample-001']),
                Code::OK,
                '/cart',
                [],
                'flow-customer-purchase',
            ],
            'checkout login leads to the registration form' => [
                'page://self/shopping/login#link=self:goCustomerRegistration',
                Code::OK,
                '/entry',
                [],
                'flow-customer-registration',
            ],
            'shipping address pages lead to each other' => [
                'page://self/shopping/shipping#link=self:goShoppingShippingEdit,self:goShoppingShipping,self:goShoppingShippingMultiple',
                Code::OK,
                '/shopping/shipping-multiple',
                [],
                'flow-customer-purchase',
            ],
            'multiple shipping editor leads back to the list' => [
                'page://self/shopping/shipping-multiple-edit#link=self:goShoppingShippingMultiple',
                Code::OK,
                '/shopping/shipping-multiple',
                [],
                'flow-customer-purchase',
            ],
            'checkout error leads back to the cart' => [
                'page://self/shopping/error#link=self:goCart',
                Code::OK,
                '/cart',
                [],
                'flow-customer-purchase',
            ],
            'completed order leads back to the top and on to the cart' => [
                'page://self/shopping/complete#link=self:goTop,self:goCart',
                Code::OK,
                '/cart',
                [],
                'flow-customer-purchase',
            ],
        ];
    }

    /**
     * Stories an admin follows. Same row shape as followable().
     *
     * @return array<string, StoryRow>
     */
    public static function forAdmins(): array
    {
        return self::admin() + self::adminContentPublish() + self::adminShopConfiguration() + self::adminMaintenance() + self::adminOrders() + self::adminSystem() + self::adminOperations() + self::adminNavigation() + self::adminActions();
    }

    /** @return array<string, StoryRow> */
    private static function admin(): array
    {
        $product = 'page://self/admin/product{?productCode}#link=';
        $payment = 'page://self/admin/payment/payment{?paymentId}#link=';

        return [
            'updated product is shown' => [
                uri_template($product . 'self:doUpdateProduct{?form*}', [
                    'productCode' => 'admin-active-001',
                    'form' => self::form(['productCode' => 'admin-active-001', 'productName' => 'Story Product', 'price02' => '1500']),
                ]),
                Code::OK,
                '/admin/product',
                ['productCode' => 'admin-active-001'],
                'flow-admin-product-publish',
            ],
            'deleted product leaves the list' => [
                uri_template($product . 'self:doDeleteProduct{?form*}', [
                    'productCode' => 'admin-active-001',
                    'form' => self::form(['productCode' => 'admin-active-001']),
                ]),
                Code::OK,
                '/admin/product-list',
                [],
                'flow-manage-product',
            ],
            'link query fills the href template' => [
                uri_template('page://self/admin/class-name/class-name-list#link=self:goClassCategoryList{?classNameId}', ['classNameId' => 'cn-color']),
                Code::OK,
                '/admin/class-category/class-category-list',
                [],
                'flow-manage-product',
            ],
            'a new link puts the category list beside the class names' => [
                uri_template('page://self/admin/class-name/class-name-list#link=new:goClassCategoryList{?classNameId}', ['classNameId' => 'cn-color']),
                Code::OK,
                '/admin/class-name/class-name-list',
                ['goClassCategoryList.classNameId' => 'cn-color', 'goClassCategoryList.count' => 2],
                'flow-manage-product',
            ],
            'deleted payment method returns to the list' => [
                uri_template($payment . 'self:doDeletePayment{?form*}', ['paymentId' => 'pay-cod', 'form' => self::form(['paymentId' => 'pay-cod'])]),
                Code::OK,
                '/admin/payment/payment-list',
                [],
                'flow-admin-shop-configuration',
            ],
            'updated payment method is shown again' => [
                uri_template($payment . 'self:doUpdatePayment{?form*}', [
                    'paymentId' => 'pay-cod',
                    'form' => self::form(['paymentId' => 'pay-cod', 'paymentMethodName' => 'Story Pay', 'charge' => '100']),
                ]),
                Code::OK,
                '/admin/payment/payment',
                ['paymentId' => 'pay-cod'],
                'flow-admin-shop-configuration',
            ],
            'deleted delivery leaves the list' => [
                uri_template('page://self/admin/delivery/delivery-list#link=self:doDeleteDelivery{?form*}', ['form' => self::form(['deliveryId' => 'del-yamato'])]),
                Code::OK,
                '/admin/delivery/delivery-list',
                [],
                'flow-admin-shop-configuration',
            ],
            'page is updated from the list' => [
                uri_template('page://self/admin/page/page-list#link=self:doUpdatePage{?form*}', ['form' => self::form(['pageId' => 'pg-company', 'pageName' => 'Story Page'])]),
                Code::OK,
                '/admin/page/page',
                [],
                'flow-admin-content-publish',
            ],
            'layout list opens a layout and updates it' => [
                uri_template('page://self/admin/layout/layout-list#link=self:goLayout{?layoutId},self:doUpdateLayout{?form*}', [
                    'layoutId' => 'lo-pc-default',
                    'form' => self::form(['layoutId' => 'lo-pc-default', 'name' => 'Story Layout']),
                ]),
                Code::OK,
                '/admin/layout/layout-list',
                [],
                'flow-admin-content-publish',
            ],
            'a GET chain goes around the layout list and back' => [
                uri_template('page://self/admin/layout/layout-list#link=self:goLayout{?layoutId},self:goLayoutList,self:goLayout{?layoutId}', ['layoutId' => 'lo-pc-default']),
                Code::OK,
                '/admin/layout/layout',
                [],
                'flow-admin-content-publish',
            ],
            'unknown payment method stops the chain' => [
                uri_template('page://self/admin/payment/payment-list#link=self:goPayment{?paymentId},self:doDeletePayment{?form*}', [
                    'paymentId' => 'nope',
                    'form' => self::form(['paymentId' => 'pay-cod']),
                ]),
                Code::NOT_FOUND,
                '/admin/payment/payment',
                [],
                'flow-admin-shop-configuration',
            ],
            'bulk status change sends every product code' => [
                'page://self/admin/product-list#link=self:doBulkUpdateProductStatus?' . http_build_query(
                    self::form(['productStatus' => '2']) + ['productCodes' => ['admin-active-001', 'admin-withdrawn-001']],
                ),
                Code::OK,
                '/admin/product-list',
                [],
                'flow-manage-product',
            ],
        ];
    }

    /**
     * flow-admin-content-publish
     *
     * @return array<string, StoryRow>
     */
    private static function adminContentPublish(): array
    {
        $news = 'page://self/admin/news/news-list#link=self:goNews{?newsId}';
        $block = 'page://self/admin/block/block{?blockId}#link=';

        return [
            'news is opened from the list and updated' => [
                uri_template($news . ',self:doUpdateNews{?form*}', [
                    'newsId' => 'nw-welcome',
                    'form' => self::form(['newsTitle' => 'Story News', 'publishDate' => '2027-03-02 00:00:00', 'newsDescription' => 'Updated by a story.', 'linkMethod' => '0']),
                ]),
                Code::OK,
                '/admin/news/news',
                ['newsId' => 'nw-welcome'],
                'flow-admin-content-publish',
            ],
            'news opened from the list is deleted' => [
                uri_template($news . ',self:doDeleteNews{?form*}', ['newsId' => 'nw-welcome', 'form' => self::form([])]),
                Code::OK,
                '/admin/news/news-list',
                [],
                'flow-admin-content-publish',
            ],
            'a page is opened from the list and the list is shown again' => [
                uri_template('page://self/admin/page/page-list#link=self:goPage{?pageId},self:goPageList', ['pageId' => 'pg-company']),
                Code::OK,
                '/admin/page/page-list',
                [],
                'flow-admin-content-publish',
            ],
            'deleted page leaves the list' => [
                uri_template('page://self/admin/page/page-list#link=self:doDeletePage{?form*}', ['form' => self::form(['pageId' => 'pg-foo'])]),
                Code::OK,
                '/admin/page/page-list',
                [],
                'flow-admin-content-publish',
            ],
            'updated block returns to the block list' => [
                uri_template($block . 'self:doUpdateBlock{?form*}', [
                    'blockId' => 'bk-user',
                    'form' => self::form(['blockId' => 'bk-user', 'blockName' => 'Story Block', 'blockFileName' => 'story_block']),
                ]),
                Code::OK,
                '/admin/block/block-list',
                [],
                'flow-admin-content-publish',
            ],
            'deleted block leaves the list' => [
                uri_template($block . 'self:doDeleteBlock{?form*}', ['blockId' => 'bk-user', 'form' => self::form(['blockId' => 'bk-user'])]),
                Code::OK,
                '/admin/block/block-list',
                [],
                'flow-admin-content-publish',
            ],
            'trade law is updated' => [
                uri_template('page://self/admin/trade-law#link=self:doUpdateTradeLaw{?form*}', [
                    'form' => self::form(['tradeLawBody' => "販売業者: Story Company\n所在地: Story City\n連絡先: 03-1234-5678"]),
                ]),
                Code::OK,
                '/admin/trade-law',
                [],
                'flow-admin-content-publish',
            ],
        ];
    }

    /**
     * flow-admin-shop-configuration
     *
     * @return array<string, StoryRow>
     */
    private static function adminShopConfiguration(): array
    {
        $calendar = 'page://self/admin/calendar#link=';

        return [
            'base info is updated and shown again' => [
                uri_template('page://self/admin/base-info#link=self:doUpdateBaseInfo{?form*}', [
                    'form' => self::form([
                        'shopName' => 'Story Shop',
                        'shopKana' => 'ストーリーショップ',
                        'shopNameEng' => 'Story Shop',
                        'companyName' => 'Story Company',
                        'postalCode' => '1500001',
                        'pref' => '13',
                        'addr01' => '渋谷区',
                        'addr02' => '1-1',
                        'phoneNumber' => '0312345678',
                        'businessHour' => '10:00-18:00',
                        'shopEmail01' => 'shop@example.com',
                    ]),
                ]),
                Code::OK,
                '/admin/base-info',
                ['shopName' => 'Story Shop'],
                'flow-admin-shop-configuration',
            ],
            'base info leads to a payment method through the list' => [
                uri_template('page://self/admin/base-info#link=self:goPaymentList,self:goPayment{?paymentId}', ['paymentId' => 'pay-cod']),
                Code::OK,
                '/admin/payment/payment',
                ['paymentId' => 'pay-cod'],
                'flow-admin-shop-configuration',
            ],
            'delivery opened from the list is updated' => [
                uri_template('page://self/admin/delivery/delivery-list#link=self:goDelivery{?deliveryId},self:doUpdateDelivery{?form*}', [
                    'deliveryId' => 'del-yamato',
                    'form' => self::form(['deliveryId' => 'del-yamato', 'deliveryName' => 'Story Delivery', 'visible' => '1']),
                ]),
                Code::OK,
                '/admin/delivery/delivery',
                ['deliveryId' => 'del-yamato'],
                'flow-admin-shop-configuration',
            ],
            'deleted tax rule leaves the list' => [
                uri_template('page://self/admin/tax-rule/tax-rule-list#link=self:doDeleteTaxRule{?form*}', ['form' => self::form(['taxRuleId' => 'tax-8'])]),
                Code::OK,
                '/admin/tax-rule/tax-rule-list',
                [],
                'flow-admin-shop-configuration',
            ],
            'holiday is added to the calendar' => [
                uri_template($calendar . 'self:doCreateCalendarHoliday{?form*}', [
                    'form' => self::form(['operation' => 'create', 'title' => 'Story Holiday', 'holiday' => '2027-02-11']),
                ]),
                Code::OK,
                '/admin/calendar',
                [],
                'flow-admin-shop-configuration',
            ],
            'holiday is updated in the calendar' => [
                uri_template($calendar . 'self:doUpdateCalendar{?form*}', [
                    'form' => self::form(['operation' => 'update', 'calendarId' => '1', 'title' => 'Story Holiday', 'holiday' => '2027-02-12']),
                ]),
                Code::OK,
                '/admin/calendar',
                [],
                'flow-admin-shop-configuration',
            ],
            'holiday is deleted from the calendar' => [
                uri_template($calendar . 'self:doDeleteCalendarHoliday{?form*}', ['form' => self::form(['calendarId' => '1'])]),
                Code::OK,
                '/admin/calendar',
                [],
                'flow-admin-shop-configuration',
            ],
        ];
    }

    /**
     * flow-admin-class-maintenance, flow-admin-category-maintenance, flow-admin-tag-maintenance,
     * flow-admin-mail-template-maintenance, flow-admin-customer-maintenance
     *
     * @return array<string, StoryRow>
     */
    private static function adminMaintenance(): array
    {
        $category = 'page://self/admin/category/category-list#link=self:goCategory{?categoryId}';
        $customerId = '0123456789abcdef0123456789abcdef';

        return [
            'class name is updated from the list' => [
                uri_template('page://self/admin/class-name/class-name-list#link=self:doUpdateClassName{?form*}', [
                    'form' => self::form(['classNameId' => 'cn-color', 'classNameLabel' => 'Story Color']),
                ]),
                Code::OK,
                '/admin/class-name/class-name-list',
                [],
                'flow-manage-product',
            ],
            'deleted class name leaves the list' => [
                uri_template('page://self/admin/class-name/class-name-list#link=self:doDeleteClassName{?form*}', ['form' => self::form(['classNameId' => 'cn-color'])]),
                Code::OK,
                '/admin/class-name/class-name-list',
                [],
                'flow-manage-product',
            ],
            'class categories are opened and the class names are shown again' => [
                uri_template('page://self/admin/class-name/class-name-list#link=self:goClassCategoryList{?classNameId},self:goClassNameList', ['classNameId' => 'cn-color']),
                Code::OK,
                '/admin/class-name/class-name-list',
                [],
                'flow-manage-product',
            ],
            'class category is updated from the list' => [
                uri_template('page://self/admin/class-category/class-category-list{?classNameId}#link=self:doUpdateClassCategory{?form*}', [
                    'classNameId' => 'cn-color',
                    'form' => self::form(['classCategoryId' => 'cc-red', 'classCategoryName' => 'Story Red']),
                ]),
                Code::OK,
                '/admin/class-category/class-category-list',
                [],
                'flow-manage-product',
            ],
            'deleted class category leaves the list' => [
                uri_template('page://self/admin/class-category/class-category-list{?classNameId}#link=self:doDeleteClassCategory{?form*}', [
                    'classNameId' => 'cn-color',
                    'form' => self::form(['classCategoryId' => 'cc-red']),
                ]),
                Code::OK,
                '/admin/class-category/class-category-list',
                [],
                'flow-manage-product',
            ],
            'class category list leads back to the class names' => [
                uri_template('page://self/admin/class-category/class-category-list{?classNameId}#link=self:goClassNameList', ['classNameId' => 'cn-color']),
                Code::OK,
                '/admin/class-name/class-name-list',
                [],
                'flow-manage-product',
            ],
            'category is opened from the list' => [
                uri_template($category, ['categoryId' => 'cat-food']),
                Code::OK,
                '/admin/category/category',
                ['categoryId' => 'cat-food'],
                'flow-manage-product',
            ],
            'category opened from the list is updated' => [
                uri_template($category . ',self:doUpdateCategory{?form*}', [
                    'categoryId' => 'cat-food',
                    'form' => self::form(['categoryId' => 'cat-food', 'categoryName' => 'Story Food']),
                ]),
                Code::OK,
                '/admin/category/category',
                ['categoryId' => 'cat-food'],
                'flow-manage-product',
            ],
            'category opened from the list is deleted' => [
                uri_template($category . ',self:doDeleteCategory{?form*}', ['categoryId' => 'cat-food', 'form' => self::form(['categoryId' => 'cat-food'])]),
                Code::OK,
                '/admin/category/category-list',
                [],
                'flow-manage-product',
            ],
            'categories are exported' => [
                'page://self/admin/category/category-list#link=self:goExportCategory',
                Code::OK,
                '/admin/category/csv',
                ['rowCount' => 2],
                'flow-admin-csv-exchange',
            ],
            'deleted tag leaves the list' => [
                uri_template('page://self/admin/tag/tag-list#link=self:doDeleteTag{?form*}', ['form' => self::form(['tagId' => 'tg-new'])]),
                Code::OK,
                '/admin/tag/tag-list',
                [],
                'flow-manage-product',
            ],
            'mail template subject is updated' => [
                uri_template('page://self/admin/mail-template#link=self:doUpdateMailTemplate{?form*}', [
                    'form' => self::form(['mailTemplateId' => '1', 'mailSubject' => 'Story subject']),
                ]),
                Code::OK,
                '/admin/mail-template',
                [],
                'flow-admin-mail-template-maintenance',
            ],
            'deleted mail template leaves the page' => [
                uri_template('page://self/admin/mail-template#link=self:doDeleteMailTemplate{?form*}', ['form' => self::form(['mailTemplateId' => '1'])]),
                Code::OK,
                '/admin/mail-template',
                [],
                'flow-admin-mail-template-maintenance',
            ],
            'mail template leads to the payment methods' => [
                'page://self/admin/mail-template#link=self:goPaymentList',
                Code::OK,
                '/admin/payment/payment-list',
                [],
                'flow-admin-shop-configuration',
            ],
            'customer is opened from the list and the list is shown again' => [
                uri_template('page://self/admin/customer-list#link=self:goCustomer{?customerId},self:goCustomerList', ['customerId' => $customerId]),
                Code::OK,
                '/admin/customer-list',
                [],
                'flow-manage-customer',
            ],
            'customer profile is updated' => [
                uri_template('page://self/admin/customer{?customerId}#link=self:doUpdateCustomerProfile{?form*}', [
                    'customerId' => $customerId,
                    'form' => self::form(['customerId' => $customerId, 'email' => 'alice@example.com', 'name01' => '更新', 'name02' => '顧客', 'phoneNumber' => '0399998888']),
                ]),
                Code::OK,
                '/admin/customer',
                ['customerId' => $customerId],
                'flow-manage-customer',
            ],
            'deleted customer leaves the list' => [
                uri_template('page://self/admin/customer-list#link=self:doDeleteCustomer{?form*}', ['form' => self::form(['customerId' => $customerId])]),
                Code::OK,
                '/admin/customer-list',
                [],
                'flow-manage-customer',
            ],
            'delivery address editor leads back to the customer list' => [
                uri_template('page://self/admin/customer-delivery-edit{?customerId}#link=self:goCustomerList', ['customerId' => $customerId]),
                Code::OK,
                '/admin/customer-list',
                [],
                'flow-manage-customer',
            ],
        ];
    }

    /**
     * flow-admin-order-fulfillment, flow-manage-order
     *
     * @return array<string, StoryRow>
     */
    private static function adminOrders(): array
    {
        return [
            'order exports lead to each other and back to the list' => [
                'page://self/admin/order/export-order#link=self:goExportShipping,self:goOrderList',
                Code::OK,
                '/admin/order-list',
                [],
                'flow-admin-order-fulfillment',
            ],
            'order pdf leads back to the list' => [
                'page://self/admin/order/order-pdf#link=self:goOrderList',
                Code::OK,
                '/admin/order-list',
                [],
                'flow-admin-order-fulfillment',
            ],
        ];
    }

    /**
     * flow-admin-auth, flow-admin-system-operation, flow-manage-system
     *
     * @return array<string, StoryRow>
     */
    private static function adminSystem(): array
    {
        $members = 'page://self/admin/member-list#link=';
        $owner = 'shop-owner';

        return [
            'admin logs in and is asked for the second factor' => [
                uri_template('page://self/admin/login#link=self:doAdminLogin{?form*}', [
                    'form' => self::form(['loginId' => 'test-admin', 'password' => 'local-dev-admin-password']),
                ]),
                Code::OK,
                '/admin/two-factor-auth',
                [],
                'flow-admin-system-operation',
            ],
            'admin logs out from the top page' => [
                uri_template('page://self/admin/index#link=self:doAdminLogout{?form*}', ['form' => self::form([])]),
                Code::OK,
                '/admin/login',
                [],
                'flow-admin-system-operation',
            ],
            'second factor pages lead back to the login' => [
                'page://self/admin/two-factor-auth#link=self:goAdminLogin',
                Code::OK,
                '/admin/login',
                [],
                'flow-admin-system-operation',
            ],
            'second factor setup leads back to the login' => [
                'page://self/admin/two-factor-auth-set#link=self:goAdminLogin',
                Code::OK,
                '/admin/login',
                [],
                'flow-admin-system-operation',
            ],
            'admin top leads to the members and a member' => [
                uri_template('page://self/admin/index#link=self:goMemberList,self:goMember{?loginId}', ['loginId' => $owner]),
                Code::OK,
                '/admin/member',
                ['loginId' => $owner],
                'flow-admin-system-operation',
            ],
            'member is opened from the list and the list is shown again' => [
                uri_template($members . 'self:goMember{?loginId},self:goMemberList', ['loginId' => $owner]),
                Code::OK,
                '/admin/member-list',
                [],
                'flow-admin-system-operation',
            ],
            'authority role is changed from the member list' => [
                uri_template($members . 'self:doUpdateAuthorityRole{?form*}', ['form' => self::form(['loginId' => $owner, 'authority' => '0'])]),
                Code::OK,
                '/admin/authority-role',
                ['loginId' => $owner],
                'flow-admin-system-operation',
            ],
            'member is updated from the list' => [
                uri_template($members . 'self:doUpdateMember{?form*}', ['form' => self::form(['loginId' => $owner, 'name' => 'Story Owner'])]),
                Code::OK,
                '/admin/member',
                ['loginId' => $owner],
                'flow-admin-system-operation',
            ],
            'member is deleted from the list' => [
                uri_template($members . 'self:doDeleteMember{?form*}', ['form' => self::form(['loginId' => $owner])]),
                Code::OK,
                '/admin/member',
                ['loginId' => $owner],
                'flow-admin-system-operation',
            ],
            'login history leads to the security page which is updated' => [
                uri_template('page://self/admin/login-history#link=self:goSecurity,self:doUpdateSecurity{?form*}', ['form' => self::form([])]),
                Code::OK,
                '/admin/security',
                [],
                'flow-admin-system-operation',
            ],
            'maintenance mode is switched on' => [
                uri_template('page://self/admin/content/maintenance#link=self:doToggleMaintenance{?form*}', ['form' => self::form(['enabled' => '1'])]),
                Code::OK,
                '/admin/content/maintenance',
                [],
                'flow-admin-system-operation',
            ],
            'cache is cleared' => [
                uri_template('page://self/admin/content/cache#link=self:doClearCache{?form*}', ['form' => self::form([])]),
                Code::OK,
                '/admin/content/cache',
                [],
                'flow-admin-system-operation',
            ],
        ];
    }

    /**
     * flow-admin-template-lifecycle, flow-admin-master-data-update, flow-admin-csv-exchange, flow-admin-content-publish, flow-admin-product-publish
     *
     * @return array<string, StoryRow>
     */
    private static function adminOperations(): array
    {
        $templates = 'page://self/admin/template/template-list#link=';
        $productClass = 'page://self/admin/product/product-class{?productCode}#link=';
        $product = ['productCode' => 'admin-active-001'];

        return [
            'admin top leads to the cache page' => [
                'page://self/admin/index#link=self:goContentCache',
                Code::OK,
                '/admin/content/cache',
                [],
                'flow-admin-system-operation',
            ],
            'news opened from the list is shown in the list again' => [
                uri_template('page://self/admin/news/news-list#link=self:goNews{?newsId},self:goNewsList', ['newsId' => 'nw-welcome']),
                Code::OK,
                '/admin/news/news-list',
                [],
                'flow-admin-content-publish',
            ],
            'css is updated' => [
                uri_template('page://self/admin/content/css#link=self:doUpdateContentCss{?form*}', ['form' => self::form(['css' => 'body { color: red; }'])]),
                Code::OK,
                '/admin/content/css',
                [],
                'flow-admin-content-publish',
            ],
            'js is updated' => [
                uri_template('page://self/admin/content/js#link=self:doUpdateContentJs{?form*}', ['form' => self::form(['js' => 'console.log(1);'])]),
                Code::OK,
                '/admin/content/js',
                [],
                'flow-admin-content-publish',
            ],
            'template is selected and the install page is opened' => [
                uri_template($templates . 'self:doSelectTemplate{?form*},self:goTemplateInstall', ['form' => self::form(['templateId' => 'tp-default-sp'])]),
                Code::OK,
                '/admin/template/template-add',
                [],
                'flow-admin-template-lifecycle',
            ],
            'template is deleted from the list' => [
                uri_template($templates . 'self:doDeleteTemplate{?form*}', ['form' => self::form(['templateId' => 'tp-default-sp'])]),
                Code::OK,
                '/admin/template/template-list',
                [],
                'flow-admin-template-lifecycle',
            ],
            'install page leads back to the template list' => [
                'page://self/admin/template/template-add#link=self:goTemplateList',
                Code::OK,
                '/admin/template/template-list',
                [],
                'flow-admin-template-lifecycle',
            ],
            'master data is selected and updated' => [
                uri_template('page://self/admin/master-data#link=self:doSelectMasterData{?select*},self:doUpdateMasterData{?update*}', [
                    'select' => self::form(['masterType' => 'tag']),
                    'update' => self::form(['masterType' => 'tag']),
                ]),
                Code::OK,
                '/admin/master-data',
                [],
                'flow-admin-master-data-update',
            ],
            'class names are imported from the exported csv' => [
                uri_template('page://self/admin/class-name/class-name-export#link=self:doImportClassNameCsv{?form*}', ['form' => self::form(['csv' => "規格名ID,規格名\r\ncn-color,Color\r\n"])]),
                Code::OK,
                '/admin/class-name/class-name-list',
                [],
                'flow-admin-csv-exchange',
            ],
            'class categories are imported from the exported csv' => [
                uri_template('page://self/admin/class-category/class-category-export#link=self:doImportClassCategoryCsv{?form*}', ['form' => self::form(['csv' => "規格分類ID,規格ID,規格分類名\r\ncc-red,cn-color,Red\r\n"])]),
                Code::OK,
                '/admin/class-category/class-category-list',
                [],
                'flow-admin-csv-exchange',
            ],
            'customer csv leads to the class name csv' => [
                'page://self/admin/customer-csv#link=self:goExportClassName',
                Code::OK,
                '/admin/class-name/class-name-export',
                [],
                'flow-admin-csv-exchange',
            ],
            'product list leads to the product csv' => [
                'page://self/admin/product-list#link=self:goExportProduct',
                Code::OK,
                '/admin/product-csv',
                [],
                'flow-admin-csv-exchange',
            ],
            'product class page leads to the product and the list' => [
                uri_template($productClass . 'self:goProduct{?productCode},self:goProductList', $product),
                Code::OK,
                '/admin/product-list',
                [],
                'flow-admin-product-publish',
            ],
            'product classes are registered and the list is shown' => [
                uri_template($productClass . 'self:doRegisterProductClass{?form*},self:goProductList', $product + ['form' => self::form($product + ['price02' => '1000', 'stock' => '5'])]),
                Code::OK,
                '/admin/product-list',
                [],
                'flow-admin-product-publish',
            ],
        ];
    }

    /**
     * flow-admin-shop-configuration, flow-admin-content-publish, flow-admin-csv-exchange, flow-admin-security-management
     *
     * @return array<string, StoryRow>
     */
    private static function adminNavigation(): array
    {
        $rows = [];
        $top = [
            'goBaseInfo' => ['shop settings', '/admin/base-info', 'flow-admin-shop-configuration'],
            'goBlockList' => ['block list', '/admin/block/block-list', 'flow-admin-content-publish'],
            'goClassCategoryList' => ['class category list', '/admin/class-category/class-category-list', 'flow-manage-product'],
            'goClassNameList' => ['class name list', '/admin/class-name/class-name-list', 'flow-manage-product'],
            'goCustomerList' => ['customer list', '/admin/customer-list', 'flow-manage-customer'],
            'goDeliveryList' => ['delivery list', '/admin/delivery/delivery-list', 'flow-admin-shop-configuration'],
            'goLayoutList' => ['layout list', '/admin/layout/layout-list', 'flow-admin-content-publish'],
            'goLoginHistoryList' => ['login history', '/admin/login-history', 'flow-admin-system-operation'],
            'goMailTemplateList' => ['mail template list', '/admin/mail-template', 'flow-admin-mail-template-maintenance'],
            'goNewsList' => ['news list', '/admin/news/news-list', 'flow-admin-content-publish'],
            'goOrderList' => ['order list', '/admin/order-list', 'flow-admin-order-fulfillment'],
            'goPageList' => ['page list', '/admin/page/page-list', 'flow-admin-content-publish'],
            'goPaymentList' => ['payment list', '/admin/payment/payment-list', 'flow-admin-shop-configuration'],
            'goPluginList' => ['plugin list', '/admin/plugin-list', 'flow-manage-plugin'],
            'goProductList' => ['product list', '/admin/product-list', 'flow-admin-product-publish'],
            'goTagList' => ['tag list', '/admin/tag/tag-list', 'flow-manage-product'],
            'goTaxRuleList' => ['tax rule list', '/admin/tax-rule/tax-rule-list', 'flow-admin-shop-configuration'],
            'goTemplateList' => ['template list', '/admin/template/template-list', 'flow-admin-template-lifecycle'],
            'goTradeLawList' => ['trade law page', '/admin/trade-law', 'flow-admin-content-publish'],
        ];
        foreach ($top as $rel => [$page, $path, $flow]) {
            $rows['admin top leads to the ' . $page] = ['page://self/admin/index#link=self:' . $rel, Code::OK, $path, [], $flow];
        }

        return $rows + [
            'product list leads to the category list and its csv' => [
                'page://self/admin/product-list#link=self:goCategoryList,self:goExportCategory',
                Code::OK,
                '/admin/category/csv',
                [],
                'flow-manage-product',
            ],
            'category edit leads to the product list' => [
                'page://self/admin/category/edit#link=self:goProductList',
                Code::OK,
                '/admin/product-list',
                [],
                'flow-admin-product-publish',
            ],
            'category list leads to the order csv' => [
                'page://self/admin/category/category-list#link=self:goExportOrder',
                Code::OK,
                '/admin/order/export-order',
                [],
                'flow-admin-csv-exchange',
            ],
            'class name list leads to the class category csv' => [
                'page://self/admin/class-name/class-name-list#link=self:goExportClassCategory',
                Code::OK,
                '/admin/class-category/class-category-export',
                [],
                'flow-admin-csv-exchange',
            ],
            'order list leads to the customer csv' => [
                'page://self/admin/order-list#link=self:goExportCustomer',
                Code::OK,
                '/admin/customer-csv',
                [],
                'flow-admin-csv-exchange',
            ],
            'customer list leads to the class name csv' => [
                'page://self/admin/customer-list#link=self:goExportClassName',
                Code::OK,
                '/admin/class-name/class-name-export',
                [],
                'flow-admin-csv-exchange',
            ],
            'payment, delivery and tax rule lists follow one another to the calendar' => [
                'page://self/admin/payment/payment-list#link=self:goDeliveryList,self:goTaxRuleList,self:goCalendar',
                Code::OK,
                '/admin/calendar',
                [],
                'flow-admin-shop-configuration',
            ],
            'news, page, block and layout lists follow one another' => [
                'page://self/admin/news/news-list#link=self:goPageList,self:goBlockList,self:goLayoutList',
                Code::OK,
                '/admin/layout/layout-list',
                [],
                'flow-admin-content-publish',
            ],
            'trade law leads to the css page and the js page' => [
                'page://self/admin/trade-law#link=self:goContentCss,self:goContentJs',
                Code::OK,
                '/admin/content/js',
                [],
                'flow-admin-content-publish',
            ],
            'cache leads to maintenance and the system information' => [
                'page://self/admin/content/cache#link=self:goMaintenance,self:goSystemInfo',
                Code::OK,
                '/admin/system',
                [],
                'flow-admin-system-operation',
            ],
            'security leads to the second factor setup and back to the verification' => [
                'page://self/admin/security#link=self:goTwoFactorAuthSet,self:goTwoFactorAuth',
                Code::OK,
                '/admin/two-factor-auth',
                [],
                'flow-admin-system-operation',
            ],
            'authority role leads to the login history' => [
                'page://self/admin/authority-role#link=self:goLoginHistoryList',
                Code::OK,
                '/admin/login-history',
                [],
                'flow-admin-system-operation',
            ],
        ];
    }

    /**
     * flow-admin-shop-configuration, flow-manage-plugin, flow-admin-template-lifecycle, flow-admin-csv-exchange, flow-admin-system-operation
     *
     * @return array<string, StoryRow>
     */
    private static function adminActions(): array
    {
        $plugin = self::form(['pluginCode' => 'Sample/DisabledPlugin']);

        return [
            'tag is moved in the sort order' => [
                uri_template('page://self/admin/tag/tag-list#link=self:doSortNoMove{?form*}', ['form' => self::form(['masterType' => 'tag', 'rowId' => 'tg-new', 'sortNo' => '2'])]),
                Code::OK,
                '/admin/sort-no-move',
                [],
                'flow-admin-shop-configuration',
            ],
            'delivery is hidden from the list' => [
                uri_template('page://self/admin/delivery/delivery-list#link=self:doToggleVisible{?form*}', ['form' => self::form(['masterType' => 'delivery', 'rowId' => 'del-yamato', 'visible' => '0'])]),
                Code::OK,
                '/admin/toggle-visible',
                [],
                'flow-admin-shop-configuration',
            ],
            'class category is added to the class' => [
                uri_template('page://self/admin/class-category/class-category-list#link=self:doCreateClassCategory{?form*}', ['form' => self::form(['classNameId' => 'cn-color', 'classCategoryName' => 'Blue'])]),
                Code::OK,
                '/admin/class-category/class-category-list',
                [],
                'flow-manage-product',
            ],
            'categories are imported from csv' => [
                uri_template('page://self/admin/category/category-list#link=self:doImportCategoryCsv{?form*}', ['form' => self::form(['csv' => "categoryId,categoryName\r\ncat-story,Story\r\n"])]),
                Code::OK,
                '/admin/category/category-list',
                [],
                'flow-admin-csv-exchange',
            ],
            'plugin is enabled' => [
                uri_template('page://self/admin/plugin-list#link=self:doEnablePlugin{?form*}', ['form' => $plugin]),
                Code::OK,
                '/admin/plugin-enable',
                [],
                'flow-manage-plugin',
            ],
            'plugin is disabled' => [
                uri_template('page://self/admin/plugin-list#link=self:doDisablePlugin{?form*}', ['form' => $plugin]),
                Code::OK,
                '/admin/plugin-disable',
                [],
                'flow-manage-plugin',
            ],
            'plugin is uninstalled' => [
                uri_template('page://self/admin/plugin-list#link=self:doUninstallPlugin{?form*}', ['form' => $plugin]),
                Code::OK,
                '/admin/plugin',
                [],
                'flow-manage-plugin',
            ],
            'shipping csv is imported' => [
                uri_template('page://self/admin/order/export-shipping#link=self:doImportShippingCsv{?form*}', ['form' => self::form(['csv' => "shippingId,trackingNumber\r\n"])]),
                Code::OK,
                '/admin/order/import-shipping',
                [],
                'flow-admin-csv-exchange',
            ],
        ];
    }

    /**
     * Stories a signed-in customer follows, run with StoryModule::MEMBER_ID. Same row shape as followable(), but not part
     * of it: the HTTP apps have no customer signed in.
     *
     * @return array<string, StoryRow>
     */
    public static function forMembers(): array
    {
        return self::memberAccount() + self::memberAddresses() + self::memberPurchase();
    }

    /**
     * Stories of a customer who has ordered before, run with StoryModule::BUYER_ID.
     *
     * The Fake order history belongs to this customer, who has no profile, so the dashboard is out of reach.
     *
     * @return array<string, StoryRow>
     */
    public static function forBuyers(): array
    {
        $order = self::PAST_ORDER;

        return [
            'order history opens a past order' => [
                uri_template('page://self/mypage/order-history#link=self:goMypageHistory{?orderNo}', ['orderNo' => $order]),
                Code::OK,
                '/mypage/history',
                ['orderNo' => $order],
                'flow-customer-account-maintenance',
            ],
            'a past order leads to its product' => [
                uri_template('page://self/mypage/history{?orderNo}#link=self:goProduct{?productCode}', ['orderNo' => $order, 'productCode' => 'sample-001']),
                Code::OK,
                '/product',
                ['productCode' => 'sample-001'],
                'flow-customer-purchase',
            ],
            'a past order is bought again' => [
                uri_template('page://self/mypage/history{?orderNo}#link=self:doReorder{?form*}', ['orderNo' => $order, 'form' => self::form(['orderNo' => $order])]),
                Code::OK,
                '/cart',
                [],
                'flow-customer-purchase',
            ],
        ];
    }

    /**
     * flow-customer-account-maintenance: the dashboard, the profile, the favorites and the withdrawal
     *
     * @return array<string, StoryRow>
     */
    private static function memberAccount(): array
    {
        $profile = self::form([
            'email' => 'alice@example.com',
            'name01' => '山田',
            'name02' => 'アリス',
            'kana01' => 'ヤマダ',
            'kana02' => 'アリス',
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => '13',
            'addr01' => '渋谷区',
            'addr02' => '神南1-1',
        ]);
        $favorite = self::form(['productCode' => 'sample-001']);

        return [
            'dashboard leads to the order history' => [
                'page://self/mypage#link=self:goOrderHistory',
                Code::OK,
                '/mypage/order-history',
                [],
                'flow-customer-account-maintenance',
            ],
            'dashboard leads to the product list' => [
                'page://self/mypage#link=self:goProductList',
                Code::OK,
                '/products',
                [],
                'flow-customer-purchase',
            ],
            'profile change ends on its completion page and returns to the dashboard' => [
                uri_template('page://self/mypage#link=self:goMypageChange,self:doUpdateCustomer{?form*},self:goMypageChangeComplete,self:goMypage', ['form' => $profile]),
                Code::OK,
                '/mypage',
                [],
                'flow-customer-account-maintenance',
            ],
            'profile change page leads back to the dashboard' => [
                'page://self/mypage/change#link=self:goMypage',
                Code::OK,
                '/mypage',
                [],
                'flow-customer-account-maintenance',
            ],
            'favorite is added and removed from the favorite list' => [
                uri_template('page://self/mypage#link=self:goFavoriteList,self:doAddFavorite{?favorite*},self:doRemoveFavorite{?favorite*}', ['favorite' => $favorite]),
                Code::OK,
                '/mypage/favorite-list',
                [],
                'flow-customer-account-maintenance',
            ],
            'withdrawal is confirmed and completed' => [
                uri_template('page://self/mypage#link=self:goMypageWithdraw,self:goMypageWithdrawConfirm,self:doWithdrawCustomer{?form*}', ['form' => self::form([])]),
                Code::OK,
                '/mypage/withdraw-complete',
                [],
                'flow-customer-account-maintenance',
            ],
            'withdrawal confirmation leads back to the dashboard' => [
                'page://self/mypage/withdraw-confirm#link=self:goMypage',
                Code::OK,
                '/mypage',
                [],
                'flow-customer-account-maintenance',
            ],
            'favorite list leads to the withdrawal' => [
                'page://self/mypage/favorite-list#link=self:goMypageWithdraw',
                Code::OK,
                '/mypage/withdraw',
                [],
                'flow-customer-account-maintenance',
            ],
        ];
    }

    /**
     * flow-customer-account-maintenance: the address book
     *
     * @return array<string, StoryRow>
     */
    private static function memberAddresses(): array
    {
        $address = ['name01' => '山田', 'name02' => '花子', 'postalCode' => '1500001', 'pref' => '13', 'addr01' => '渋谷区', 'addr02' => '神南1-1', 'phoneNumber' => '0312345678'];

        return [
            'profile change leads to the address list and adds an address' => [
                uri_template(
                    'page://self/mypage/change-complete#link=self:goCustomerAddressList,self:doCreateCustomerAddress{?form*}',
                    ['form' => self::form($address)],
                ),
                Code::OK,
                '/mypage/address-list',
                [],
                'flow-customer-account-maintenance',
            ],
            'address is updated and deleted from the address list' => [
                uri_template(
                    'page://self/mypage/address-list#link=self:doUpdateCustomerAddress{?update*},self:doDeleteCustomerAddress{?delete*}',
                    ['update' => self::form(['addressId' => self::MEMBER_ADDRESS, 'addr01' => '港区']), 'delete' => self::form(['addressId' => self::MEMBER_ADDRESS])],
                ),
                Code::OK,
                '/mypage/address-list',
                [],
                'flow-customer-account-maintenance',
            ],
            'address page leads back to the address list' => [
                uri_template('page://self/mypage/address{?addressId}#link=self:goCustomerAddressList', ['addressId' => self::MEMBER_ADDRESS]),
                Code::OK,
                '/mypage/address-list',
                [],
                'flow-customer-account-maintenance',
            ],
            'address list leads to the dashboard and on to the favorites' => [
                'page://self/mypage/address-list#link=self:goMypage,self:goFavoriteList',
                Code::OK,
                '/mypage/favorite-list',
                [],
                'flow-customer-account-maintenance',
            ],
        ];
    }

    /**
     * flow-customer-purchase: the cart and the checkout of a signed-in customer
     *
     * @return array<string, StoryRow>
     */
    private static function memberPurchase(): array
    {
        $item = self::form(['productCode' => 'sample-001', 'quantity' => '1']);
        $confirm = self::form(['preOrderId' => self::PRE_ORDER, 'payment' => '2']);
        $checkout = self::form(['preOrderId' => self::PRE_ORDER]);
        $checkoutChain = 'page://self/mypage#link=self:goCart,self:goCheckoutEntry,self:doConfirmOrder{?confirm*},self:doCheckout{?checkout*}';
        $entry = 'page://self/cart#link=self:goCheckoutEntry,';

        return [
            'dashboard leads to the cart and a product is added' => [
                uri_template('page://self/mypage#link=self:goCart,self:doAddCartItem{?item*}', ['item' => $item]),
                Code::OK,
                '/cart',
                [],
                'flow-customer-purchase',
            ],
            'checkout is confirmed and completed' => [
                uri_template($checkoutChain, ['confirm' => $confirm, 'checkout' => $checkout]),
                Code::OK,
                '/shopping/complete',
                [],
                'flow-customer-purchase',
            ],
            'completed order leads to the dashboard' => [
                uri_template($checkoutChain . ',self:goMypage', ['confirm' => $confirm, 'checkout' => $checkout]),
                Code::OK,
                '/mypage',
                [],
                ['flow-customer-purchase', 'flow-customer-account-maintenance'],
            ],
            'completed order leads back to the cart' => [
                'page://self/shopping/complete#link=self:goCart',
                Code::OK,
                '/cart',
                [],
                'flow-customer-purchase',
            ],
            'shipping address is chosen' => [
                uri_template($entry . 'self:goShoppingShipping,self:doSelectShippingAddress{?form*}', ['form' => self::form(['shippingAddressId' => self::MEMBER_ADDRESS])]),
                Code::OK,
                '/shopping',
                [],
                'flow-customer-purchase',
            ],
            'shipping address is edited' => [
                uri_template($entry . 'self:goShoppingShippingEdit,self:doUpdateShippingAddress{?form*}', ['form' => self::form(['name01' => '山田', 'name02' => '花子', 'postalCode' => '1500001', 'pref' => '13', 'addr01' => '渋谷区', 'addr02' => '神南1-1', 'phoneNumber' => '0312345678'])]),
                Code::OK,
                '/shopping',
                [],
                'flow-customer-purchase',
            ],
            'shipping choice leads to the multiple shipping page and back to checkout' => [
                $entry . 'self:goShoppingShipping,self:goShoppingShippingMultiple,self:goShopping',
                Code::OK,
                '/shopping',
                [],
                'flow-customer-purchase',
            ],
            'shipping edit leads back to the shipping choice' => [
                'page://self/shopping/shipping-edit#link=self:goShoppingShipping',
                Code::OK,
                '/shopping/shipping',
                [],
                'flow-customer-purchase',
            ],
            'checkout of a signed-in customer ends on the error page and leads back to the cart' => [
                'page://self/shopping#link=self:goShoppingError,self:goCart',
                Code::OK,
                '/cart',
                [],
                'flow-customer-purchase',
            ],
        ];
    }

    /**
     * The URI and the exception that stops the story.
     *
     * @return array<string, array{string, class-string<\Throwable>}>
     */
    public static function unfollowable(): array
    {
        return [
            'the index has no submit link' => [
                uri_template('page://self/#link=self:doSubmitContact{?form*}', ['form' => self::form(self::inquiry())]),
                LinkRelException::class,
            ],
            'an id made during the story is not in the URI' => [
                uri_template('page://self/admin/news/news-list#link=self:doUpdateNews{?form*}', ['form' => self::form(['newsTitle' => 'Unreachable'])]),
                ParameterException::class,
            ],
            'a link declared only on write methods is missing from the page that is read' => [
                uri_template('page://self/admin/delivery/delivery{?deliveryId}#link=self:goDeliveryList', ['deliveryId' => 'del-yamato']),
                LinkRelException::class,
            ],
            'the tracking number is declared only on the shipping address write method' => [
                uri_template('page://self/admin/order/shipping-address{?orderNo}#link=self:doUpdateTrackingNumber{?form*}', [
                    'orderNo' => 'past0000000000000000000000000001',
                    'form' => self::form(['orderNo' => 'past0000000000000000000000000001', 'trackingNumber' => 'TRK0001']),
                ]),
                LinkRelException::class,
            ],
            'the password form does not declare its submit link' => [
                uri_template('page://self/admin/change-password#link=self:doChangePassword{?form*}', ['form' => self::form(['currentPassword' => 'a', 'newPassword' => 'b'])]),
                LinkRelException::class,
            ],
            'the block list declares the block page only on write methods' => [
                'page://self/admin/block/block-list#link=self:goBlock',
                LinkRelException::class,
            ],
            'the delivery address editor declares the customer page only on write methods' => [
                uri_template('page://self/admin/customer-delivery-edit{?customerId}#link=self:goCustomer', ['customerId' => '0123456789abcdef0123456789abcdef']),
                LinkRelException::class,
            ],
            'the guest checkout location is a page that redirects a guest to the login' => [
                uri_template('page://self/shopping/non-member#link=self:doSubmitNonMember{?form*}', ['form' => self::form(self::nonMember())]),
                LinkException::class,
            ],
        ];
    }

    /**
     * The first request uploads a file: its URI and query, the file, then the code and path the story ends on.
     *
     * @return array<string, array{string, array<string, string>, string, int, string}>
     */
    public static function uploads(): array
    {
        return [
            'installed template opens the install form again' => [
                'page://self/admin/template/template-add#link=self:goTemplateInstall',
                ['templateCode' => 'story-template', 'templateName' => 'Story Template', 'csrfToken' => FakeCsrfToken::DEFAULT_TOKEN],
                dirname(__DIR__) . '/fixtures/template-upload.zip',
                Code::OK,
                '/admin/template/template-add',
            ],
        ];
    }

    /**
     * Stories that read back what they wrote, so they need the SQL context. Same row shape as followable().
     *
     * @return array<string, StoryRow>
     */
    public static function persisted(): array
    {
        $created = 'page://self/admin/news/news-list#link=self:doCreateNews{?created*}';
        $updated = $created . ',self:doUpdateNews{?updated*}';
        $news = [
            'created' => self::form([
                'newsTitle' => 'Story News',
                'publishDate' => '2027-03-01 00:00:00',
                'newsDescription' => 'Created by a story.',
                'newsUrl' => 'https://example.com/story-news',
                'linkMethod' => '0',
            ]),
            'updated' => self::form([
                'newsTitle' => 'Story News Updated',
                'publishDate' => '2027-03-02 00:00:00',
                'newsDescription' => 'Updated by a story.',
                'newsUrl' => 'https://example.com/story-news-updated',
                'linkMethod' => '0',
            ]),
            'deleted' => self::form([]),
        ];

        return self::persistedNews($news, $created, $updated) + self::persistedCreates();
    }

    /**
     * @param array<string, array<string, string>> $news
     *
     * @return array<string, StoryRow>
     */
    private static function persistedNews(array $news, string $created, string $updated): array
    {
        return [
            'created news is shown at its location' => [uri_template($created, $news), Code::OK, '/admin/news/news', ['newsTitle' => 'Story News'], 'flow-admin-content-publish'],
            'news id fills the href template of the next link' => [uri_template($updated, $news), Code::OK, '/admin/news/news', ['newsTitle' => 'Story News Updated'], 'flow-admin-content-publish'],
            'deleted news leaves the list' => [
                uri_template($updated . ',self:doDeleteNews{?deleted*}', $news),
                Code::OK,
                '/admin/news/news-list',
                [],
                'flow-admin-content-publish',
            ],
            'visitor registers from the top page' => [
                uri_template('page://self/#link=self:goCustomerRegistration,self:doRegisterCustomer{?form*}', [
                    'form' => self::form([
                        'email' => 'story@example.com',
                        'email_confirm' => 'story@example.com',
                        'password' => 'Story-pass-2026',
                        'password_confirm' => 'Story-pass-2026',
                        'name01' => '物語',
                        'name02' => '太郎',
                        'kana01' => 'モノガタリ',
                        'kana02' => 'タロウ',
                        'phoneNumber' => '0312345678',
                        'postalCode' => '1000001',
                        'pref' => '13',
                        'addr01' => '千代田区',
                        'addr02' => '千代田1-1',
                    ]),
                ]),
                Code::OK,
                '/entry/complete',
                [],
                'flow-customer-registration',
            ],
        ];
    }

    /**
     * flow-manage-*: every create transition reads the created resource back at its Location.
     *
     * @return array<string, StoryRow>
     */
    private static function persistedCreates(): array
    {
        return [
            'created page is shown at its location' => [
                uri_template('page://self/admin/page/page-list#link=self:doCreatePage{?form*}', ['form' => self::form(['pageName' => 'Story Page', 'pageUrl' => 'story-page', 'pageFileName' => 'story_page'])]),
                Code::OK,
                '/admin/page/page',
                ['pageName' => 'Story Page'],
                'flow-admin-content-publish',
            ],
            'created block is shown at its location' => [
                uri_template('page://self/admin/block/block-list#link=self:doCreateBlock{?form*}', ['form' => self::form(['blockName' => 'Story Block', 'blockFileName' => 'story_block_new'])]),
                Code::OK,
                '/admin/block/block',
                ['blockName' => 'Story Block'],
                'flow-admin-content-publish',
            ],
            'created product is shown at its location' => [
                uri_template('page://self/admin/product-new#link=self:doCreateProduct{?form*}', ['form' => self::form(['productCode' => 'story-product', 'productName' => 'Story Product', 'price02' => '2468', 'stock' => '8', 'productStatus' => '1', 'description' => 'Created by a story.'])]),
                Code::OK,
                '/admin/product',
                ['productName' => 'Story Product'],
                'flow-admin-product-publish',
            ],
            'created category is shown at its location' => [
                uri_template('page://self/admin/category/category-list#link=self:doCreateCategory{?form*}', ['form' => self::form(['categoryName' => 'Story Category', 'sortNo' => '90'])]),
                Code::OK,
                '/admin/category/category',
                ['categoryName' => 'Story Category'],
                'flow-manage-product',
            ],
            'created class name is shown at its location' => [
                uri_template('page://self/admin/class-name/class-name-list#link=self:doCreateClassName{?form*}', ['form' => self::form(['classNameLabel' => 'Story Class'])]),
                Code::OK,
                '/admin/class-name/class-name-list',
                [],
                'flow-manage-product',
            ],
            'created payment method is shown at its location' => [
                uri_template('page://self/admin/payment/payment-list#link=self:doCreatePayment{?form*}', ['form' => self::form(['paymentMethodName' => 'Story Pay', 'charge' => '220', 'ruleMin' => '0', 'ruleMax' => '999999', 'visible' => '1'])]),
                Code::OK,
                '/admin/payment/payment',
                ['payment.paymentMethodName' => 'Story Pay'],
                'flow-admin-shop-configuration',
            ],
            'created delivery is shown at its location' => [
                uri_template('page://self/admin/delivery/delivery-list#link=self:doCreateDelivery{?form*}', ['form' => self::form(['deliveryName' => 'Story Delivery', 'visible' => '1'])]),
                Code::OK,
                '/admin/delivery/delivery',
                ['delivery.deliveryName' => 'Story Delivery'],
                'flow-admin-shop-configuration',
            ],
            'created tag is shown at its location' => [
                uri_template('page://self/admin/tag/tag-list#link=self:doCreateTag{?form*}', ['form' => self::form(['tagName' => 'Story Tag'])]),
                Code::OK,
                '/admin/tag/tag-list',
                [],
                'flow-manage-product',
            ],
            'created customer is shown at its location' => [
                uri_template('page://self/admin/customer-list#link=self:doCreateCustomer{?form*}', ['form' => self::form(['email' => 'story-customer@example.com', 'password' => 'Story-pass-2026', 'name01' => '管理', 'name02' => '顧客', 'kana01' => 'カンリ', 'kana02' => 'コキャク', 'phoneNumber' => '0312345678', 'postalCode' => '1000001', 'pref' => '13', 'addr01' => '千代田区', 'addr02' => '管理1-1'])]),
                Code::OK,
                '/admin/customer',
                ['email' => 'story-customer@example.com'],
                'flow-manage-customer',
            ],
        ];
    }

    /** @return array<string, string> */
    private static function nonMember(): array
    {
        return [
            'name01' => '山田',
            'name02' => '太郎',
            'kana01' => 'ヤマダ',
            'kana02' => 'タロウ',
            'email' => 'taro@example.com',
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => '13',
            'addr01' => '渋谷',
            'addr02' => '1-1',
        ];
    }

    /** @return array<string, string> */
    private static function inquiry(): array
    {
        return [
            'contactName01' => '山田',
            'contactName02' => '太郎',
            'contactEmail' => 'yamada@example.com',
            'contactContents' => 'A story asks a question.',
        ];
    }

    /**
     * The fields a form sends, with the CSRF token the page carries
     *
     * @param array<string, string> $fields
     *
     * @return array<string, string>
     */
    private static function form(array $fields): array
    {
        return $fields + ['csrfToken' => FakeCsrfToken::DEFAULT_TOKEN];
    }
}
