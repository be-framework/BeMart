<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Story;

use BEAR\Resource\Code;
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
        return self::forGuests() + self::forAdmins();
    }

    /**
     * Stories a guest follows. Same row shape as followable().
     *
     * @return array<string, array{string, int, string, array<string, mixed>}>
     */
    public static function forGuests(): array
    {
        $inquiry = 'page://self/#link=self:goContactForm,self:doSubmitContact{?form*}';
        $item = self::form(['productCode' => 'sample-001', 'quantity' => '1']);

        return [
            'inquiry is answered with a ticket' => [
                uri_template($inquiry, ['form' => self::form(self::inquiry())]),
                Code::OK,
                '/contact/complete',
                [],
            ],
            'rejected inquiry stops the chain' => [
                uri_template($inquiry . ',self:goTop', ['form' => self::form(['contactEmail' => 'not-an-email', 'mode' => 'contact'] + self::inquiry())]),
                Code::BAD_REQUEST,
                '/contact',
                [],
            ],
            'guest adds a cart item and is sent to checkout as a non-member' => [
                uri_template('page://self/cart#link=self:doAddCartItem{?item*},self:goCheckoutEntry,self:goShoppingNonMember', ['item' => $item]),
                Code::OK,
                '/shopping/non-member',
                [],
            ],
            'guest finds a product in the list and puts it in the cart' => [
                uri_template(
                    'page://self/#link=self:goProductList{?nameKeyword},self:goProduct{?productCode},self:doAddCartItem{?item*}',
                    ['nameKeyword' => 'sample', 'productCode' => 'sample-001', 'item' => $item],
                ),
                Code::OK,
                '/cart',
                [],
            ],
        ];
    }

    /**
     * Stories an admin follows. Same row shape as followable().
     *
     * @return array<string, array{string, int, string, array<string, mixed>}>
     */
    public static function forAdmins(): array
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
            ],
            'deleted product leaves the list' => [
                uri_template($product . 'self:doDeleteProduct{?form*}', [
                    'productCode' => 'admin-active-001',
                    'form' => self::form(['productCode' => 'admin-active-001']),
                ]),
                Code::OK,
                '/admin/product-list',
                [],
            ],
            'link query fills the href template' => [
                uri_template('page://self/admin/class-name/class-name-list#link=self:goClassCategoryList{?classNameId}', ['classNameId' => 'cn-color']),
                Code::OK,
                '/admin/class-category/class-category-list',
                [],
            ],
            'a new link puts the category list beside the class names' => [
                uri_template('page://self/admin/class-name/class-name-list#link=new:goClassCategoryList{?classNameId}', ['classNameId' => 'cn-color']),
                Code::OK,
                '/admin/class-name/class-name-list',
                [],
            ],
            'deleted payment method returns to the list' => [
                uri_template($payment . 'self:doDeletePayment{?form*}', ['paymentId' => 'pay-cod', 'form' => self::form(['paymentId' => 'pay-cod'])]),
                Code::OK,
                '/admin/payment/payment-list',
                [],
            ],
            'updated payment method is shown again' => [
                uri_template($payment . 'self:doUpdatePayment{?form*}', [
                    'paymentId' => 'pay-cod',
                    'form' => self::form(['paymentId' => 'pay-cod', 'paymentMethodName' => 'Story Pay', 'charge' => '100']),
                ]),
                Code::OK,
                '/admin/payment/payment',
                ['paymentId' => 'pay-cod'],
            ],
            'deleted delivery leaves the list' => [
                uri_template('page://self/admin/delivery/delivery-list#link=self:doDeleteDelivery{?form*}', ['form' => self::form(['deliveryId' => 'del-yamato'])]),
                Code::OK,
                '/admin/delivery/delivery-list',
                [],
            ],
            'page is updated from the list' => [
                uri_template('page://self/admin/page/page-list#link=self:doUpdatePage{?form*}', ['form' => self::form(['pageId' => 'pg-company', 'pageName' => 'Story Page'])]),
                Code::OK,
                '/admin/page/page',
                [],
            ],
            'layout list opens a layout and updates it' => [
                uri_template('page://self/admin/layout/layout-list#link=self:goLayout{?layoutId},self:doUpdateLayout{?form*}', [
                    'layoutId' => 'lo-pc-default',
                    'form' => self::form(['layoutId' => 'lo-pc-default', 'name' => 'Story Layout']),
                ]),
                Code::OK,
                '/admin/layout/layout-list',
                [],
            ],
            'a GET chain goes around the layout list and back' => [
                uri_template('page://self/admin/layout/layout-list#link=self:goLayout{?layoutId},self:goLayoutList,self:goLayout{?layoutId}', ['layoutId' => 'lo-pc-default']),
                Code::OK,
                '/admin/layout/layout',
                [],
            ],
            'unknown payment method stops the chain' => [
                uri_template('page://self/admin/payment/payment-list#link=self:goPayment{?paymentId},self:doDeletePayment{?form*}', [
                    'paymentId' => 'nope',
                    'form' => self::form(['paymentId' => 'pay-cod']),
                ]),
                Code::NOT_FOUND,
                '/admin/payment/payment',
                [],
            ],
            'bulk status change sends every product code' => [
                'page://self/admin/product-list#link=self:doBulkUpdateProductStatus?' . http_build_query(
                    self::form(['productStatus' => '2']) + ['productCodes' => ['admin-active-001', 'admin-withdrawn-001']],
                ),
                Code::OK,
                '/admin/product-list',
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
            'the index has no submit link' => [
                uri_template('page://self/#link=self:doSubmitContact{?form*}', ['form' => self::form(self::inquiry())]),
                LinkRelException::class,
            ],
            'an id made during the story is not in the URI' => [
                uri_template('page://self/admin/news/news-list#link=self:doUpdateNews{?form*}', ['form' => self::form(['newsTitle' => 'Unreachable'])]),
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

    /**
     * Stories that read back what they wrote, so they need the SQL context. Same row shape as followable().
     *
     * @return array<string, array{string, int, string, array<string, mixed>}>
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

        return [
            'created news is shown at its location' => [uri_template($created, $news), Code::OK, '/admin/news/news', ['newsTitle' => 'Story News']],
            'news id fills the href template of the next link' => [uri_template($updated, $news), Code::OK, '/admin/news/news', ['newsTitle' => 'Story News Updated']],
            'deleted news leaves the list' => [
                uri_template($updated . ',self:doDeleteNews{?deleted*}', $news),
                Code::OK,
                '/admin/news/news-list',
                [],
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
