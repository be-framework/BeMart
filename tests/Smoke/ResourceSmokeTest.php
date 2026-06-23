<?php

namespace MyVendor\BeMart\Tests\Smoke;

use BEAR\AppMeta\Meta as AppMeta;
use BEAR\Resource\Code;
use BEAR\Resource\Meta as ResourceMeta;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function assert;
use function array_diff;
use function array_keys;
use function array_values;
use function dirname;
use function explode;
use function in_array;
use function is_array;
use function ksort;
use function md5;
use function parse_str;
use function parse_url;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function strtoupper;

/**
 * @psalm-type NormalizedCase = array{0: string, 1: string, 2: array<string, mixed>, 3: int, 4: string|null}
 */
final class ResourceSmokeTest extends TestCase
{
    private const ALICE_CUSTOMER_ID = '0123456789abcdef0123456789abcdef';

    /** @var array<string, string> */
    private const RESOURCE_METHODS = [
        'GET' => 'get',
        'POST' => 'post',
        'PUT' => 'put',
        'PATCH' => 'patch',
        'DELETE' => 'delete',
    ];

    /** @var array<string, string> */
    private const SESSION_CUSTOMER_IDS = [
        'DELETE page://self/mypage/address' => self::ALICE_CUSTOMER_ID,
        'GET page://self/mypage' => self::ALICE_CUSTOMER_ID,
        'GET page://self/mypage/change' => self::ALICE_CUSTOMER_ID,
        'GET page://self/mypage/withdraw' => self::ALICE_CUSTOMER_ID,
        'GET page://self/shopping' => self::ALICE_CUSTOMER_ID,
        'POST page://self/mypage/change' => self::ALICE_CUSTOMER_ID,
        'POST page://self/mypage/withdraw' => self::ALICE_CUSTOMER_ID,
        'PUT page://self/mypage/address' => self::ALICE_CUSTOMER_ID,
    ];

    /** @var array<string, true>|null */
    private static array|null $targetIdentities = null;

    #[DataProvider('resourceProvider')]
    public function testResourceObjectSmoke(
        string $method,
        string $uri,
        array $params,
        int $expectedCode,
        string|null $sessionCustomerId,
    ): void {
        $resource = $this->resource($method, $uri, $sessionCustomerId);
        $call = self::RESOURCE_METHODS[$method];

        try {
            $ro = $resource->{$call}($uri, $params);
        } catch (\MyVendor\BeMart\Be\Exception\AddressNotFoundException $e) {
            $this->assertSame('POST page://self/admin/order/shipping-address', $method . ' ' . $uri);
            $this->assertSame(Code::NOT_FOUND, $expectedCode);

            return;
        }

        $this->assertSame($expectedCode, $ro->code);
    }

    public function testFixtureMatchesResourceTargets(): void
    {
        $targets = array_keys(self::targetIdentities());
        $fixtures = array_keys(self::resourceProvider());

        Assert::assertSame([], array_values(array_diff($targets, $fixtures)), 'Missing fixture');
        Assert::assertSame([], array_values(array_diff($fixtures, $targets)), 'Extra fixture');
    }

    /**
     * @return array<string, NormalizedCase>
     */
    public static function resourceProvider(): array
    {
        return self::normalize([
            'DELETE page://self/admin/block/block?blockId=bk-user' => Code::OK,
            'DELETE page://self/admin/calendar' => Code::OK,
            'DELETE page://self/admin/category/category?categoryId=cat-food' => Code::OK,
            'DELETE page://self/admin/class-category/class-category?classCategoryId=cc-red' => Code::OK,
            'DELETE page://self/admin/class-name/class-name?classNameId=cn-color' => Code::OK,
            'DELETE page://self/admin/customer-delivery-edit?customerId=0123456789abcdef0123456789abcdef&addressId=addr00000000000000000000000000a1' => Code::OK,
            'DELETE page://self/admin/delivery/delivery?deliveryId=del-yamato' => Code::OK,
            'DELETE page://self/admin/mail-template?mailTemplateId=1' => Code::OK,
            'DELETE page://self/admin/member?loginId=shop-owner' => Code::OK,
            'DELETE page://self/admin/news/news?newsId=nw-welcome' => Code::OK,
            'DELETE page://self/admin/page/page?pageId=pg-company' => Code::OK,
            'DELETE page://self/admin/payment/payment?paymentId=pay-cod' => Code::OK,
            'DELETE page://self/admin/plugin?pluginCode=Sample%2FSamplePlugin' => Code::OK,
            'DELETE page://self/admin/product?productCode=admin-active-001' => Code::OK,
            'DELETE page://self/admin/tag/tag?tagId=tg-new' => Code::OK,
            'DELETE page://self/admin/tax-rule/tax-rule?taxRuleId=tax-10' => Code::OK,
            'DELETE page://self/admin/template/template-list?templateId=default' => Code::OK,
            'DELETE page://self/cart/item?productCode=sample-001' => Code::OK,
            'DELETE page://self/mypage/address?addressId=addr00000000000000000000000000a1' => Code::OK,
            'DELETE page://self/mypage/favorite?productCode=sample-001' => Code::OK,
            'GET app://self/agent/catalog?nameKeyword=%E3%82%B5%E3%83%B3%E3%83%97%E3%83%AB&limit=5' => Code::OK,
            'GET app://self/agent/product?productCode=sample-001' => Code::OK,
            'GET page://self/action-redirect' => Code::OK,
            'GET page://self/admin/action-redirect' => Code::OK,
            'GET page://self/admin/authority-role' => Code::OK,
            'GET page://self/admin/base-info' => Code::OK,
            'GET page://self/admin/block/block' => Code::OK,
            'GET page://self/admin/block/block-list' => Code::OK,
            'GET page://self/admin/calendar' => Code::OK,
            'GET page://self/admin/category/category?categoryId=cat-food' => Code::OK,
            'GET page://self/admin/category/category-list' => Code::OK,
            'GET page://self/admin/category/csv' => Code::OK,
            'GET page://self/admin/category/edit' => Code::OK,
            'GET page://self/admin/change-password' => Code::OK,
            'GET page://self/admin/class-category/class-category-export' => Code::OK,
            'GET page://self/admin/class-category/class-category-list' => Code::OK,
            'GET page://self/admin/class-name/class-name-export' => Code::OK,
            'GET page://self/admin/class-name/class-name-list' => Code::OK,
            'GET page://self/admin/content/cache' => Code::OK,
            'GET page://self/admin/content/css' => Code::OK,
            'GET page://self/admin/content/file-manager' => Code::OK,
            'GET page://self/admin/content/js' => Code::OK,
            'GET page://self/admin/content/maintenance' => Code::OK,
            'GET page://self/admin/csv-config' => Code::OK,
            'GET page://self/admin/customer?email=alice%40example.com' => Code::OK,
            'GET page://self/admin/customer-csv' => Code::OK,
            'GET page://self/admin/customer-delivery-edit' => Code::OK,
            'GET page://self/admin/customer-list' => Code::OK,
            'GET page://self/admin/delivery/delivery' => Code::OK,
            'GET page://self/admin/delivery/delivery-list' => Code::OK,
            'GET page://self/admin/empty-page' => Code::OK,
            'GET page://self/admin/index' => Code::OK,
            'GET page://self/admin/layout/layout' => Code::OK,
            'GET page://self/admin/layout/layout-list' => Code::OK,
            'GET page://self/admin/log' => Code::OK,
            'GET page://self/admin/login' => Code::OK,
            'GET page://self/admin/login-history' => Code::OK,
            'GET page://self/admin/mail-template' => Code::OK,
            'GET page://self/admin/master-data' => Code::OK,
            'GET page://self/admin/member' => Code::OK,
            'GET page://self/admin/member-list' => Code::OK,
            'GET page://self/admin/news/news?newsId=nw-welcome' => Code::OK,
            'GET page://self/admin/news/news-list' => Code::OK,
            'GET page://self/admin/order?orderNo=past0000000000000000000000000001' => Code::OK,
            'GET page://self/admin/order-list' => Code::OK,
            'GET page://self/admin/order-status' => Code::OK,
            'GET page://self/admin/order/edit' => Code::OK,
            'GET page://self/admin/order/export-order' => Code::OK,
            'GET page://self/admin/order/export-order-pdf?orderNos%5B0%5D=past0000000000000000000000000001' => Code::OK,
            'POST page://self/admin/order/export-order-pdf?orderNos%5B0%5D=past0000000000000000000000000001' => Code::OK,
            'GET page://self/admin/order/export-shipping' => Code::OK,
            'GET page://self/admin/order/import-shipping' => Code::OK,
            'GET page://self/admin/order/mail-confirm' => Code::OK,
            'GET page://self/admin/order/order-pdf' => Code::OK,
            'GET page://self/admin/order/send-mail' => Code::OK,
            'GET page://self/admin/order/shipping-address' => Code::OK,
            'GET page://self/admin/order/shipping-notify-mail?orderNo=past0000000000000000000000000001' => Code::OK,
            'GET page://self/admin/page/page?pageId=pg-company' => Code::OK,
            'GET page://self/admin/page/page-list' => Code::OK,
            'GET page://self/admin/payment/payment' => Code::OK,
            'GET page://self/admin/payment/payment-list' => Code::OK,
            'GET page://self/admin/plugin-list' => Code::OK,
            'GET page://self/admin/product?productCode=admin-active-001' => Code::OK,
            'GET page://self/admin/product-csv' => Code::OK,
            'GET page://self/admin/product-list' => Code::OK,
            'GET page://self/admin/product-new' => Code::OK,
            'GET page://self/admin/product/csv-category' => Code::OK,
            'GET page://self/admin/product/csv-class-category' => Code::OK,
            'GET page://self/admin/product/csv-class-name' => Code::OK,
            'GET page://self/admin/product/csv-product' => Code::OK,
            'GET page://self/admin/product/edit' => Code::OK,
            'GET page://self/admin/product/product-class' => Code::OK,
            'GET page://self/admin/security' => Code::OK,
            'GET page://self/admin/system' => Code::OK,
            'GET page://self/admin/tag/tag-list' => Code::OK,
            'GET page://self/admin/tax-rule/tax-rule-list' => Code::OK,
            'GET page://self/admin/template/template-add' => Code::OK,
            'GET page://self/admin/template/template-list' => Code::OK,
            'GET page://self/admin/trade-law' => Code::OK,
            'GET page://self/admin/two-factor-auth' => Code::OK,
            'GET page://self/admin/two-factor-auth-edit' => Code::OK,
            'GET page://self/admin/two-factor-auth-set' => Code::OK,
            'GET page://self/admin/unsupported-route' => Code::OK,
            'GET page://self/cart' => Code::OK,
            'GET page://self/contact' => Code::OK,
            'GET page://self/contact/complete' => Code::OK,
            'GET page://self/contact/confirm' => Code::OK,
            'GET page://self/entry' => Code::OK,
            'GET page://self/entry/activate' => Code::OK,
            'GET page://self/entry/complete' => Code::OK,
            'GET page://self/entry/confirm' => Code::OK,
            'GET page://self/forgot-complete' => Code::OK,
            'GET page://self/forgot-password' => Code::OK,
            'GET page://self/help/about' => Code::OK,
            'GET page://self/help/agreement' => Code::OK,
            'GET page://self/help/guide' => Code::OK,
            'GET page://self/help/privacy' => Code::OK,
            'GET page://self/help/trade-law' => Code::OK,
            'GET page://self/index' => Code::OK,
            'GET page://self/login' => Code::OK,
            'GET page://self/mypage' => Code::OK,
            'GET page://self/mypage/address' => Code::OK,
            'GET page://self/mypage/address-list' => Code::OK,
            'GET page://self/mypage/change' => Code::OK,
            'GET page://self/mypage/change-complete' => Code::OK,
            'GET page://self/mypage/favorite-list' => Code::OK,
            'GET page://self/mypage/history?orderNo=past0000000000000000000000000001' => Code::OK,
            'GET page://self/mypage/order-history' => Code::OK,
            'GET page://self/mypage/withdraw' => Code::OK,
            'GET page://self/mypage/withdraw-complete' => Code::OK,
            'GET page://self/mypage/withdraw-confirm' => Code::OK,
            'GET page://self/product?productCode=sample-001' => Code::OK,
            'GET page://self/products?category_id=' => Code::OK,
            'GET page://self/reset' => Code::OK,
            'GET page://self/shopping' => Code::OK,
            'GET page://self/shopping/complete?orderNo=past0000000000000000000000000001' => Code::OK,
            'GET page://self/shopping/confirm' => Code::OK,
            'GET page://self/shopping/error' => Code::OK,
            'GET page://self/shopping/login' => Code::OK,
            'GET page://self/shopping/non-member' => Code::OK,
            'GET page://self/shopping/shipping' => Code::OK,
            'GET page://self/shopping/shipping-edit' => Code::OK,
            'GET page://self/shopping/shipping-multiple' => Code::OK,
            'GET page://self/shopping/shipping-multiple-edit' => Code::OK,
            'GET page://self/unsupported-route' => Code::OK,
            'POST page://self/action-redirect' => Code::OK,
            'POST page://self/admin/action-redirect' => Code::OK,
            'POST page://self/admin/authority-role?loginId=shop-owner&authority=1' => Code::OK,
            'POST page://self/admin/base-info?shopName=smoke' => Code::OK,
            'POST page://self/admin/block/block-list?blockName=Smoke%20Block&blockFileName=smoke_block' => Code::CREATED,
            'POST page://self/admin/calendar' => Code::OK,
            'POST page://self/admin/category/category-list?categoryName=Smoke%20Category&sortNo=9' => Code::CREATED,
            'POST page://self/admin/category/csv?csv=%E3%82%AB%E3%83%86%E3%82%B4%E3%83%AAID%2C%E3%82%AB%E3%83%86%E3%82%B4%E3%83%AA%E5%90%8D%2C%E8%A6%AA%E3%82%AB%E3%83%86%E3%82%B4%E3%83%AAID%2C%E3%82%AB%E3%83%86%E3%82%B4%E3%83%AA%E5%89%8A%E9%99%A4%E3%83%95%E3%83%A9%E3%82%B0%0Acat-food%2C%E9%A3%9F%E5%93%81%2C%2C0%0A' => Code::OK,
            'POST page://self/admin/change-password?currentPassword=local-dev-admin-password&changePasswordFirst=new-strong-password-2026&changePasswordSecond=new-strong-password-2026' => Code::OK,
            'POST page://self/admin/class-category/class-category-list?classNameId=cn-color&classCategoryName=Smoke%20Category' => Code::CREATED,
            'POST page://self/admin/class-name/class-name-list?classNameLabel=Smoke%20Class' => Code::CREATED,
            'POST page://self/admin/create-customer?email=smoke-a1e2c345%40example.com&password=smoke-passphrase-2026&name01=%E5%B1%B1%E7%94%B0&name02=%E5%A4%AA%E9%83%8E' => Code::CREATED,
            'POST page://self/admin/csv-config?csvType=1&columns%5B0%5D%5BcolumnName%5D=productCode&columns%5B0%5D%5Benabled%5D=1&columns%5B0%5D%5BsortNo%5D=1&columns%5B1%5D%5BcolumnName%5D=productName&columns%5B1%5D%5Benabled%5D=1&columns%5B1%5D%5BsortNo%5D=2' => Code::OK,
            'POST page://self/admin/customer/resend-activation-mail?email=provisional%40example.com' => Code::OK,
            'POST page://self/admin/customer?customerId=0123456789abcdef0123456789abcdef&email=alice%40example.com&name01=Yamada&name02=Taro' => Code::OK,
            'POST page://self/admin/customer-delivery-edit?customerId=0123456789abcdef0123456789abcdef&name01=Yamada&name02=Taro&postalCode=1500001&pref=13&addr01=Shibuya&addr02=1-1-1&phoneNumber=0312345678' => Code::CREATED,
            'POST page://self/admin/delete-customer?customerId=0123456789abcdef0123456789abcdef' => Code::OK,
            'POST page://self/admin/delivery/delivery-list?deliveryName=Smoke%20Delivery' => Code::CREATED,
            'POST page://self/admin/login?loginId=test-admin&password=local-dev-admin-password' => Code::OK,
            'POST page://self/admin/logout' => Code::SEE_OTHER,
            'POST page://self/admin/mail-template?mailTemplateId=1&mailSubject=Smoke%20subject' => Code::OK,
            'POST page://self/admin/mail-template/create?mailTemplateName=Smoke%20Template&fileName=Mail%2Fsmoke_template.twig&mailSubject=Smoke%20mail%20subject' => Code::CREATED,
            'POST page://self/admin/member?loginId=fresh-admin&password=smoke-passphrase-2026&name=%E6%96%B0%E4%BA%BA%E7%AE%A1%E7%90%86%E8%80%85&authority=1' => Code::CREATED,
            'POST page://self/admin/news/news-list?newsTitle=Smoke%20News&publishDate=2026-01-01T00%3A00%3A00%2B09%3A00' => Code::CREATED,
            'POST page://self/admin/order-status?orderNo=past0000000000000000000000000001&orderStatus=3' => Code::OK,
            'POST page://self/admin/order/bulk-delete?orderNos%5B0%5D=past0000000000000000000000000001' => Code::OK,
            'POST page://self/admin/order/create?customerId=0123456789abcdef0123456789abcdef&paymentMethodId=1&orderItems%5B0%5D%5BproductCode%5D=sample-001&orderItems%5B0%5D%5BproductName%5D=%E3%82%B5%E3%83%B3%E3%83%97%E3%83%AB%E5%95%86%E5%93%81%20A&orderItems%5B0%5D%5BunitPrice%5D=1200&orderItems%5B0%5D%5Bquantity%5D=1' => Code::CREATED,
            'POST page://self/admin/order/import-shipping?csv=%E5%8F%97%E6%B3%A8%E7%95%AA%E5%8F%B7%2C%E3%81%8A%E5%95%8F%E3%81%84%E5%90%88%E3%82%8F%E3%81%9B%E7%95%AA%E5%8F%B7%0Apast0000000000000000000000000001%2CTRACK123456789%0A' => Code::OK,
            'POST page://self/admin/order/send-mail?orderNo=past0000000000000000000000000001' => Code::OK,
            'POST page://self/admin/product/product-class?productCode=admin-active-001&stock=10' => Code::CREATED,
            'POST page://self/admin/two-factor-auth-edit?deviceToken=000000' => Code::FORBIDDEN,
            'POST page://self/admin/order/shipping-address?orderNo=past0000000000000000000000000001&addressId=addr00000000000000000000000000a1' => Code::NOT_FOUND,
            'POST page://self/admin/order/shipping-notify-mail?orderNo=past0000000000000000000000000001' => Code::OK,
            'POST page://self/admin/page/page-list?pageName=Smoke%20Page&pageUrl=smoke-page&pageFileName=smoke_page' => Code::CREATED,
            'POST page://self/admin/payment/payment-list?paymentMethodName=Smoke%20Payment' => Code::CREATED,
            'POST page://self/admin/plugin-disable?pluginCode=Sample%2FSamplePlugin' => Code::OK,
            'POST page://self/admin/plugin-enable?pluginCode=Sample%2FSamplePlugin' => Code::OK,
            'POST page://self/admin/plugin-list?pluginCode=Sample%2FSamplePlugin&pluginName=Sample%20Plugin&pluginVersion=1.0.0' => Code::OK,
            'POST page://self/admin/product?productCode=wave8-resource-001&productName=Smoke%20Product&price02=1200' => Code::CREATED,
            'POST page://self/admin/product-bulk-status?productCodes%5B0%5D=admin-active-001&productStatus=1' => Code::OK,
            'POST page://self/admin/product-copy?productCode=admin-active-001&newProductCode=admin-active-001.copy' => Code::CREATED,
            'POST page://self/admin/product-csv?csv=productCode%2CproductName%2Cprice02%0Asmoke-csv-product%2CCSV%20Product%2C1200%0A' => Code::OK,
            'POST page://self/admin/product/csv-class-category' => Code::OK,
            'POST page://self/admin/product/csv-class-name' => Code::OK,
            'POST page://self/admin/tag/tag-list?tagName=Smoke%20Tag' => Code::CREATED,
            'POST page://self/admin/tax-rule/tax-rule-list?taxRate=10&applyDate=2026-01-01T00%3A00%3A00%2B09%3A00' => Code::CREATED,
            'POST page://self/admin/template/template-add?templateCode=mytheme&templateName=My%20Theme' => Code::BAD_REQUEST,
            'POST page://self/admin/template/template-list?templateId=default' => Code::OK,
            'POST page://self/admin/trade-law?tradeLawBody=Smoke%20trade%20law%20text' => Code::OK,
            'POST page://self/admin/two-factor-auth?loginId=test-admin&deviceToken=123456' => Code::OK,
            'POST page://self/admin/unsupported-route' => Code::OK,
            'POST page://self/cart/item?productCode=sample-001&quantity=1' => Code::CREATED,
            'POST page://self/contact?contactName01=%E5%B1%B1%E7%94%B0&contactName02=%E5%A4%AA%E9%83%8E&contactEmail=contact-smoke%40example.com&contactContents=Smoke%20inquiry%20body' => Code::OK,
            'POST page://self/entry?email=smoke-173279df%40example.com&password=smoke-passphrase-2026&name01=%E5%B1%B1%E7%94%B0&name02=%E5%A4%AA%E9%83%8E' => Code::CREATED,
            'POST page://self/entry/activate?secretKey=pending-secret-key-pilot7-2026abcd' => Code::SEE_OTHER,
            'POST page://self/forgot-password?email=alice%40example.com' => Code::OK,
            'POST page://self/login?email=login-test%40example.com&password=local-dev-member-password' => Code::OK,
            'POST page://self/logout' => Code::SEE_OTHER,
            'POST page://self/mypage/address-list?name01=%E5%B1%B1%E7%94%B0&name02=%E5%A4%AA%E9%83%8E&postalCode=1500001&pref=13&addr01=%E6%B8%8B%E8%B0%B7%E5%8C%BA&addr02=%E7%A5%9E%E5%AE%AE%E5%89%8D1-1-1&phoneNumber=0312345678' => Code::CREATED,
            'POST page://self/mypage/change?email=smoke-215cc16e%40example.com' => Code::OK,
            'POST page://self/mypage/favorite?productCode=sample-001' => Code::CREATED,
            'POST page://self/mypage/reorder?orderNo=past0000000000000000000000000001' => Code::CREATED,
            'POST page://self/mypage/withdraw' => Code::OK,
            'POST page://self/reset?resetKey=valid-reset-key-pilot15-aaaa1111&password=smoke-passphrase-2026' => Code::OK,
            'POST page://self/shopping/checkout?preOrderId=aaaa00000000000000000000000000000000aaaa' => Code::CREATED,
            'POST page://self/shopping/confirm?preOrderId=aceface0000000000000000000000000000a11ce&payment=2' => Code::OK,
            'POST page://self/shopping/non-member?name01=%E5%B1%B1%E7%94%B0&name02=%E5%A4%AA%E9%83%8E&kana01=%E3%83%A4%E3%83%9E%E3%83%80&kana02=%E3%82%BF%E3%83%AD%E3%82%A6&email=smoke-eb56b445%40example.com&phoneNumber=0312345678&postalCode=1500001&pref=13&addr01=%E6%B8%8B%E8%B0%B7%E5%8C%BA&addr02=%E7%A5%9E%E5%AE%AE%E5%89%8D1-1-1' => Code::CREATED,
            'POST page://self/shopping/shipping' => Code::SEE_OTHER,
            'POST page://self/shopping/shipping-edit' => Code::SEE_OTHER,
            'POST page://self/shopping/shipping-multiple' => Code::SEE_OTHER,
            'POST page://self/shopping/shipping-multiple-edit' => Code::SEE_OTHER,
            'POST page://self/unsupported-route' => Code::OK,
            'PUT page://self/admin/block/block?blockId=bk-user' => Code::OK,
            'PUT page://self/admin/category/category?categoryId=cat-food' => Code::OK,
            'PUT page://self/admin/class-category/class-category?classCategoryId=cc-red' => Code::OK,
            'PUT page://self/admin/class-name/class-name?classNameId=cn-color' => Code::OK,
            'PUT page://self/admin/content/cache' => Code::OK,
            'PUT page://self/admin/content/css' => Code::OK,
            'PUT page://self/admin/content/js' => Code::OK,
            'PUT page://self/admin/content/maintenance?enabled=1' => Code::OK,
            'PUT page://self/admin/delivery/delivery?deliveryId=del-yamato' => Code::OK,
            'PUT page://self/admin/layout/layout?layoutId=lo-pc-default' => Code::OK,
            'PUT page://self/admin/master-data' => Code::OK,
            'PUT page://self/admin/master-data-edit?masterType=tag' => Code::OK,
            'PUT page://self/admin/member?loginId=shop-owner' => Code::OK,
            'PUT page://self/admin/news/news?newsId=nw-welcome' => Code::OK,
            'PUT page://self/admin/order?orderNo=past0000000000000000000000000001' => Code::OK,
            'PUT page://self/admin/order-status' => Code::OK,
            'PUT page://self/admin/order/shipping-address?orderNo=past0000000000000000000000000001&name01=%E5%B1%B1%E7%94%B0&name02=%E5%A4%AA%E9%83%8E&postalCode=1500001&pref=13&addr01=%E6%B8%8B%E8%B0%B7%E5%8C%BA&addr02=%E7%A5%9E%E5%AE%AE%E5%89%8D1-1-1&phoneNumber=0312345678' => Code::OK,
            'PUT page://self/admin/order/tracking-number?orderNo=past0000000000000000000000000001&trackingNumber=TRACK123456789' => Code::OK,
            'PUT page://self/admin/page/page?pageId=pg-company' => Code::OK,
            'PUT page://self/admin/payment/payment?paymentId=pay-cod' => Code::OK,
            'PUT page://self/admin/product?productCode=admin-active-001' => Code::OK,
            'PUT page://self/admin/security' => Code::OK,
            'PUT page://self/admin/sort-no-move?masterType=tag&rowId=tg-new&sortNo=9' => Code::OK,
            'PUT page://self/admin/template/template-list?templateId=default' => Code::OK,
            'PUT page://self/admin/toggle-visible?masterType=delivery&rowId=del-yamato&visible=0' => Code::OK,
            'PUT page://self/admin/two-factor-auth-set?loginId=fresh-admin&authKey=JBSWY3DPEHPK3PXP&deviceToken=123456' => Code::FORBIDDEN,
            'PUT page://self/cart/item?productCode=sample-001&quantity=1' => Code::OK,
            'PUT page://self/mypage/address?addressId=addr00000000000000000000000000a1' => Code::OK,
        ]);
    }

    private function resource(string $method, string $uri, string|null $sessionCustomerId): ResourceInterface
    {
        $module = new ResourceSmokeModule(
            new AppMeta('MyVendor\\BeMart', 'test'),
            str_starts_with($uri, 'page://self/admin'),
            $sessionCustomerId,
        );

        $injector = new Injector(
            $module,
            dirname(__DIR__, 2) . '/var/tmp/resource-smoke-' . md5($method . ' ' . $uri),
        );

        return $injector->getInstance(ResourceInterface::class);
    }

    /**
     * @param array<string, int> $cases
     *
     * @return array<string, NormalizedCase>
     */
    private static function normalize(array $cases): array
    {
        $normalized = [];

        foreach ($cases as $key => $code) {
            Assert::assertIsInt($code, sprintf('Code must be int: %s', $key));
            [$identity, $method, $uri, $params] = self::normalizeCase($key);
            Assert::assertArrayNotHasKey($identity, $normalized, sprintf('Duplicate fixture identity: %s', $identity));
            $normalized[$identity] = [$method, $uri, $params, $code, self::SESSION_CUSTOMER_IDS[$identity] ?? null];
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: array<string, mixed>}
     */
    private static function normalizeCase(string $key): array
    {
        Assert::assertSame(1, preg_match('/^(GET|POST|PUT|PATCH|DELETE)\\s+(\\S+)$/', $key), sprintf('Invalid fixture key: %s', $key));
        [$method, $uriWithQuery] = explode(' ', $key, 2);
        Assert::assertArrayHasKey($method, self::RESOURCE_METHODS, sprintf('Unsupported fixture method: %s', $key));

        $uriParts = parse_url($uriWithQuery);
        assert(is_array($uriParts));
        assert(isset($uriParts['scheme'], $uriParts['host'], $uriParts['path']));

        $queryParams = [];
        parse_str((string) ($uriParts['query'] ?? ''), $queryParams);
        $uri = sprintf('%s://%s%s', $uriParts['scheme'], $uriParts['host'], $uriParts['path']);
        $identity = $method . ' ' . $uri;
        $queryParams = self::normalizeTypedParams($identity, $queryParams);

        return [$identity, $method, $uri, $queryParams];
    }

    /**
     * parse_str() intentionally emulates form/query transport, which yields
     * strings. A few smoke fixtures target ResourceObject calls directly and
     * therefore must pass the already-decoded PHP value expected by the
     * JsonSchema boundary.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private static function normalizeTypedParams(string $identity, array $params): array
    {
        if ($identity === 'POST page://self/admin/csv-config') {
            if (isset($params['csvType'])) {
                $params['csvType'] = (int) $params['csvType'];
            }

            if (isset($params['columns']) && is_array($params['columns'])) {
                foreach ($params['columns'] as $index => $column) {
                    if (! is_array($column)) {
                        continue;
                    }

                    if (isset($column['enabled'])) {
                        $column['enabled'] = self::boolValue($column['enabled']);
                    }

                    if (isset($column['sortNo'])) {
                        $column['sortNo'] = (int) $column['sortNo'];
                    }

                    $params['columns'][$index] = $column;
                }
            }
        }

        if ($identity === 'POST page://self/admin/order/create') {
            if (isset($params['paymentMethodId'])) {
                $params['paymentMethodId'] = (int) $params['paymentMethodId'];
            }

            if (isset($params['orderItems']) && is_array($params['orderItems'])) {
                foreach ($params['orderItems'] as $index => $orderItem) {
                    if (! is_array($orderItem)) {
                        continue;
                    }

                    if (isset($orderItem['unitPrice'])) {
                        $orderItem['unitPrice'] = (int) $orderItem['unitPrice'];
                    }

                    if (isset($orderItem['quantity'])) {
                        $orderItem['quantity'] = (int) $orderItem['quantity'];
                    }

                    $params['orderItems'][$index] = $orderItem;
                }
            }
        }

        if ($identity === 'PUT page://self/admin/toggle-visible' && isset($params['visible'])) {
            $params['visible'] = self::boolValue($params['visible']);
        }

        return $params;
    }

    private static function boolValue(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }

    /**
     * @return array<string, true>
     */
    private static function targetIdentities(): array
    {
        if (self::$targetIdentities !== null) {
            return self::$targetIdentities;
        }

        $targets = [];
        $meta = new AppMeta('MyVendor\\BeMart', 'test');
        foreach ($meta->getGenerator('*') as $resource) {
            $resourceMeta = new ResourceMeta($resource->class);
            foreach ($resourceMeta->options->params as $params) {
                $method = strtoupper($params->method);
                if (! in_array($method, array_keys(self::RESOURCE_METHODS), true)) {
                    continue;
                }

                $identity = $method . ' ' . $resource->uriPath;
                $targets[$identity] = true;
            }
        }

        ksort($targets);

        self::$targetIdentities = $targets;

        return self::$targetIdentities;
    }

}
