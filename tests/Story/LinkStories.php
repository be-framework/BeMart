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

    /**
     * Stories that read back what they wrote, so they need the SQL context. Same row shape as followable().
     *
     * @return array<string, array{string, int, string, array<string, mixed>}>
     */
    public static function persisted(): array
    {
        $created = 'page://self/admin/news/news-list#link=self:doCreateNews?' . self::form([
            'newsTitle' => 'Story News',
            'publishDate' => '2027-03-01 00:00:00',
            'newsDescription' => 'Created by a story.',
            'newsUrl' => 'https://example.com/story-news',
            'linkMethod' => '0',
        ]);
        $updated = $created . ',self:doUpdateNews?' . self::form([
            'newsTitle' => 'Story News Updated',
            'publishDate' => '2027-03-02 00:00:00',
            'newsDescription' => 'Updated by a story.',
            'newsUrl' => 'https://example.com/story-news-updated',
            'linkMethod' => '0',
        ]);

        return [
            'created news is shown at its location' => [$created, Code::OK, '/admin/news/news', ['newsTitle' => 'Story News']],
            'news id fills the href template of the next link' => [$updated, Code::OK, '/admin/news/news', ['newsTitle' => 'Story News Updated']],
            'deleted news leaves the list' => [$updated . ',self:doDeleteNews?' . self::form([]), Code::OK, '/admin/news/news-list', []],
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
