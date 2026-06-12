<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Page;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminPageListFetched;
use MyVendor\BeMart\Be\Final\PageCreated;
use MyVendor\BeMart\Be\Input\CreatePageInput;
use MyVendor\BeMart\Be\Input\GetAdminPageListInput;
use BEAR\Resource\Annotation\JsonSchema;

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
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /** ALPS `goPageList` に対応する GET 操作。 */
    #[Alps('goPageList')]
    #[JsonSchema(schema: 'get-admin-page-page-list.json')]
    #[Link(rel: 'doCreatePage', href: 'page://self/admin/page/page-list', method: 'post')]
    #[Link(rel: 'goPage', href: 'page://self/admin/page/page', method: 'get')]
    #[Link(rel: 'doUpdatePage', href: 'page://self/admin/page/page', method: 'put')]
    #[Link(rel: 'doDeletePage', href: 'page://self/admin/page/page', method: 'delete')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetAdminPageListInput());

        assert($final instanceof AdminPageListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'pages' => $final->pages,
        ];

        return $this;
    }

    /**
     * ALPS `doCreatePage` に対応する POST 操作。
     * @psalm-taint-source input $pageName
     * @psalm-taint-source input $pageUrl
     * @psalm-taint-source input $pageFileName
     */
    #[Alps('doCreatePage')]
    #[JsonSchema(schema: 'post-admin-page-page-list.json', params: 'post-admin-page-page-list.param.json')]
    #[Link(rel: 'goPageList', href: 'page://self/admin/page/page-list')]
    #[CsrfProtected]
    public function onPost(
        string $pageName,
        string $pageUrl,
        string $pageFileName,
    ): static {
        $final = ($this->becoming)(new CreatePageInput(
            pageName: $pageName,
            pageUrl: $pageUrl,
            pageFileName: $pageFileName,
        ));

        assert($final instanceof PageCreated);

        ($this->mutationResponse)($this, Code::CREATED, sprintf('/admin/page/page?pageId=%s', urlencode($final->pageId)));
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
