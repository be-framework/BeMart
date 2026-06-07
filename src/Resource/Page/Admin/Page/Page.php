<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Page;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\PageNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminPageFetched;
use MyVendor\BeMart\Be\Final\PageDeleted;
use MyVendor\BeMart\Be\Final\PageUpdated;
use MyVendor\BeMart\Be\Input\DeletePageInput;
use MyVendor\BeMart\Be\Input\GetAdminPageInput;
use MyVendor\BeMart\Be\Input\UpdatePageInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\AdminPageForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goPage + doUpdatePage + doDeletePage — single-row endpoint
 * (Wave 9 CMS).
 *
 * Phase 3 — HTML FORM page. `onGet` exposes an {@see AdminPageForm}
 * (Ray.WebFormModule AbstractForm) as `body['form']` pre-filled with the
 * persisted row, so the admin page editor (`Content/page_edit.twig`
 * port) can render real `<input>`s via `{{ form.input(...) }}`. The JSON
 * contexts (`app`, `prod`, `test`) ignore `body['form']`.
 */
class Page extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrf,
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * ALPS `goPage` に対応する GET 操作。
     * @psalm-taint-source input $pageId
     */
    #[Alps('goPage')]
    #[JsonSchema(schema: 'get-admin-page-page.json', params: 'get-admin-page-page.param.json')]
    #[Link(rel: 'goPageList', href: 'page://self/admin/page/page-list')]
    public function onGet(string|null $pageId = null): static
    {
        if ($pageId === null || $pageId === '') {
            if ($this->adminSession->adminId === null) {
                $this->code = Code::FORBIDDEN;
                $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

                return $this;
            }

            $this->code = Code::OK;
            $this->body = [
                'pageId' => '',
                'pageName' => '',
                'pageUrl' => '',
                'pageFileName' => '',
                'pageEditType' => 1,
                'csrfToken' => $this->csrf->token,
            ];
            $form = $this->formFactory->newInstance(AdminPageForm::class);
            assert($form instanceof AdminPageForm);
            $form->fillValues($this->body);
            $this->body['form'] = $form;

            return $this;
        }

        $final = ($this->becoming)(new GetAdminPageInput(pageId: $pageId));

        assert($final instanceof AdminPageFetched);

        $this->code = Code::OK;
        $this->body = [
            'pageId' => $final->pageId,
            'pageName' => $final->pageName,
            'pageUrl' => $final->pageUrl,
            'pageFileName' => $final->pageFileName,
            'pageEditType' => $final->pageEditType,
        ];
        // Phase 3: an AdminPageForm pre-filled with the persisted row,
        // for the HTML edit page to render via `{{ form.input(...) }}`.
        $form = $this->formFactory->newInstance(AdminPageForm::class);
        assert($form instanceof AdminPageForm);
        $form->fillValues($this->body);
        $this->body['form'] = $form;

        return $this;
    }

    /**
     * ALPS `doUpdatePage` に対応する PUT 操作。
     * @psalm-taint-source input $pageId
     * @psalm-taint-source input $pageName
     * @psalm-taint-source input $pageUrl
     * @psalm-taint-source input $pageFileName
     */
    #[Alps('doUpdatePage')]
    #[JsonSchema(schema: 'put-admin-page-page.json', params: 'put-admin-page-page.param.json')]
    #[Link(rel: 'goPage', href: 'page://self/admin/page/page')]
    #[CsrfProtected]
    public function onPut(
        string $pageId,
        string|null $pageName = null,
        string|null $pageUrl = null,
        string|null $pageFileName = null,
    ): static {
        $final = ($this->becoming)(new UpdatePageInput(
            pageId: $pageId,
            pageName: $pageName,
            pageUrl: $pageUrl,
            pageFileName: $pageFileName,
        ));

        assert($final instanceof PageUpdated);

        $this->code = Code::OK;
        $this->body = [
            'pageId' => $final->pageId,
            'pageName' => $final->pageName,
            'pageUrl' => $final->pageUrl,
            'pageFileName' => $final->pageFileName,
            'pageEditType' => $final->pageEditType,
        ];

        return $this;
    }

    /**
     * ALPS `doUpdatePage` に対応する DELETE 操作。
     * @psalm-taint-source input $pageId
     */
    #[Alps('doUpdatePage')]
    #[JsonSchema(schema: 'delete-admin-page-page.json', params: 'delete-admin-page-page.param.json')]
    #[Link(rel: 'goPageList', href: 'page://self/admin/page/page-list')]
    #[Link(rel: 'goBlockList', href: 'page://self/admin/block/block-list')]
    #[CsrfProtected]
    public function onDelete(string $pageId): static
    {
        $final = ($this->becoming)(new DeletePageInput(pageId: $pageId));

        assert($final instanceof PageDeleted);

        $this->code = Code::OK;
        $this->body = ['pageId' => $final->pageId];

        return $this;
    }
}
