<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\News;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminNewsListFetched;
use MyVendor\BeMart\Be\Final\NewsCreated;
use MyVendor\BeMart\Be\Input\CreateNewsInput;
use MyVendor\BeMart\Be\Input\GetAdminNewsListInput;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goNewsList + doCreateNews — collection endpoint (Wave 9).
 */
class NewsList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'doCreateNews', href: 'page://self/admin/news/news-list', method: 'post')]
    #[Link(rel: 'goNews', href: 'page://self/admin/news/news', method: 'get')]
    #[Link(rel: 'doUpdateNews', href: 'page://self/admin/news/news', method: 'put')]
    #[Link(rel: 'doDeleteNews', href: 'page://self/admin/news/news', method: 'delete')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetAdminNewsListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminNewsListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'news' => $final->news,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $newsTitle
     * @psalm-taint-source input $publishDate
     * @psalm-taint-source input $newsDescription
     * @psalm-taint-source input $newsUrl
     * @psalm-taint-source input $linkMethod
     */
    #[Link(rel: 'goNewsList', href: 'page://self/admin/news/news-list')]
    #[CsrfProtected]
    public function onPost(
        string $newsTitle,
        string $publishDate,
        string|null $newsDescription = null,
        string|null $newsUrl = null,
        bool $linkMethod = false,
    ): static {
        try {
            $final = ($this->becoming)(new CreateNewsInput(
                newsTitle: $newsTitle,
                publishDate: $publishDate,
                newsDescription: $newsDescription,
                newsUrl: $newsUrl,
                linkMethod: $linkMethod,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof NewsCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/news/news?newsId=%s', urlencode($final->newsId));
        $this->body = [
            'newsId' => $final->newsId,
            'newsTitle' => $final->newsTitle,
            'newsDescription' => $final->newsDescription,
            'newsUrl' => $final->newsUrl,
            'publishDate' => $final->publishDate,
            'linkMethod' => $final->linkMethod,
        ];

        return $this;
    }
}
