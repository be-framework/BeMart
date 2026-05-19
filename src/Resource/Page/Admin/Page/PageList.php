<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminPageListFetched;
use MyVendor\BeMart\Be\Final\PageCreated;
use MyVendor\BeMart\Be\Input\CreatePageInput;
use MyVendor\BeMart\Be\Input\GetAdminPageListInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goPageList + doCreatePage — collection endpoint (Wave 9 CMS).
 *
 *   - GET  → goPageList    (admin lists CMS pages — safe read)
 *   - POST → doCreatePage  (admin creates a new free page)
 */
class PageList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    #[Link(rel: 'doCreatePage', href: 'page://self/admin/page/page-list', method: 'post')]
    #[Link(rel: 'goPage', href: 'page://self/admin/page/page', method: 'get')]
    #[Link(rel: 'doUpdatePage', href: 'page://self/admin/page/page', method: 'put')]
    #[Link(rel: 'doDeletePage', href: 'page://self/admin/page/page', method: 'delete')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetAdminPageListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminPageListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'pages' => $final->pages,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $pageName
     * @psalm-taint-source input $pageUrl
     * @psalm-taint-source input $pageFileName
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goPageList', href: 'page://self/admin/page/page-list')]
    public function onPost(
        string $pageName,
        string $pageUrl,
        string $pageFileName,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new CreatePageInput(
                pageName: $pageName,
                pageUrl: $pageUrl,
                pageFileName: $pageFileName,
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

        assert($final instanceof PageCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/page/page?pageId=%s', urlencode($final->pageId));
        $this->body = [
            'pageId' => $final->pageId,
            'pageName' => $final->pageName,
            'pageUrl' => $final->pageUrl,
            'pageFileName' => $final->pageFileName,
            'pageEditType' => $final->pageEditType,
        ];

        return $this;
    }
}
