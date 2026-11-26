<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\LinkRelException;
use BEAR\Resource\Exception\ParameterException;
use MyVendor\BeMart\Tests\Fake\Csrf\FakeCsrfToken;

use function dirname;
use function http_build_query;

/**
 * Stories a client follows from one URI, each named by what it shows.
 */
final class LinkStories
{
    /**
     * The URI, then the code, path and body subset of the response the story ends on.
     *
     * @return array<string, array{string, int, string, array<string, mixed>}>
     */
    public static function followable(): array
    {
        $inquiry = 'page://self/#link=self:goContactForm,self:doSubmitContact?';
        $product = 'page://self/admin/product?productCode=admin-active-001#link=';

        return [
            'inquiry is answered with a ticket' => [$inquiry . self::form(self::inquiry()), Code::OK, '/contact/complete', []],
            'rejected inquiry stops the chain' => [
                $inquiry . self::form(['contactEmail' => 'not-an-email', 'mode' => 'contact'] + self::inquiry()) . ',self:goTop',
                Code::BAD_REQUEST,
                '/contact',
                [],
            ],
            'updated product is shown' => [
                $product . 'self:doUpdateProduct?' . self::form(['productCode' => 'admin-active-001', 'productName' => 'Story Product', 'price02' => '1500']),
                Code::OK,
                '/admin/product',
                ['productCode' => 'admin-active-001'],
            ],
            'deleted product leaves the list' => [
                $product . 'self:doDeleteProduct?' . self::form(['productCode' => 'admin-active-001']),
                Code::OK,
                '/admin/product-list',
                [],
            ],
            'link query fills the href template' => [
                'page://self/admin/class-name/class-name-list#link=self:goClassCategoryList?classNameId=cn-color',
                Code::OK,
                '/admin/class-category/class-category-list',
                [],
            ],
            'deleted payment method returns to the list' => [
                'page://self/admin/payment/payment?paymentId=pay-cod#link=self:doDeletePayment?' . self::form(['paymentId' => 'pay-cod']),
                Code::OK,
                '/admin/payment/payment-list',
                [],
            ],
            'updated payment method is shown again' => [
                'page://self/admin/payment/payment?paymentId=pay-cod#link=self:doUpdatePayment?' . self::form(['paymentId' => 'pay-cod', 'paymentMethodName' => 'Story Pay', 'charge' => '100']),
                Code::OK,
                '/admin/payment/payment',
                ['paymentId' => 'pay-cod'],
            ],
            'deleted delivery leaves the list' => [
                'page://self/admin/delivery/delivery-list#link=self:doDeleteDelivery?' . self::form(['deliveryId' => 'del-yamato']),
                Code::OK,
                '/admin/delivery/delivery-list',
                [],
            ],
            'page is updated from the list' => [
                'page://self/admin/page/page-list#link=self:doUpdatePage?' . self::form(['pageId' => 'pg-company', 'name' => 'Story Page']),
                Code::OK,
                '/admin/page/page',
                [],
            ],
            'layout list opens a layout and updates it' => [
                'page://self/admin/layout/layout-list#link=self:goLayout?layoutId=lo-pc-default,self:doUpdateLayout?' . self::form(['layoutId' => 'lo-pc-default', 'name' => 'Story Layout']),
                Code::OK,
                '/admin/layout/layout-list',
                [],
            ],
            'guest adds a cart item and is sent to checkout as a non-member' => [
                'page://self/cart#link=self:doAddCartItem?' . self::form(['productCode' => 'sample-001', 'quantity' => '1']) . ',self:goCheckoutEntry,self:goShoppingNonMember',
                Code::OK,
                '/shopping/non-member',
                [],
            ],
            'a GET chain goes around the layout list and back' => [
                'page://self/admin/layout/layout-list#link=self:goLayout?layoutId=lo-pc-default,self:goLayoutList,self:goLayout?layoutId=lo-pc-default',
                Code::OK,
                '/admin/layout/layout',
                [],
            ],
            'unknown payment method stops the chain' => [
                'page://self/admin/payment/payment-list#link=self:goPayment?paymentId=nope,self:doDeletePayment?' . self::form(['paymentId' => 'pay-cod']),
                Code::NOT_FOUND,
                '/admin/payment/payment',
                [],
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
            'the index has no submit link' => ['page://self/#link=self:doSubmitContact?' . self::form(self::inquiry()), LinkRelException::class],
            'an id made during the story is not in the URI' => [
                'page://self/admin/news/news-list#link=self:doUpdateNews?' . self::form(['newsTitle' => 'Unreachable']),
                ParameterException::class,
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

    /** @param array<string, string> $fields */
    private static function form(array $fields): string
    {
        return http_build_query($fields + ['csrfToken' => FakeCsrfToken::DEFAULT_TOKEN]);
    }
}
