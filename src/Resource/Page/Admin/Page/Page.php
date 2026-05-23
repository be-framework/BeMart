<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Page;

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
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminPageForm;
use Ray\WebFormModule\FormFactory;

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
        private readonly CsrfTokenInterface $csrf,
        private readonly AdminSessionInterface $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * @psalm-taint-source input $pageId
     */
    #[Link(rel: 'goPageList', href: 'page://self/admin/page/page-list')]
    public function onGet(string|null $pageId = null): static
    {
        if ($pageId === null || $pageId === '') {
            if ($this->adminSession->adminId() === null) {
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
                'csrfToken' => FakeCsrfToken::TOKEN,
            ];
            $form = $this->formFactory->newInstance(AdminPageForm::class);
            assert($form instanceof AdminPageForm);
            $form->fillValues($this->body);
            $this->body['form'] = $form;

            return $this;
        }

        try {
            $final = ($this->becoming)(new GetAdminPageInput(pageId: $pageId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (PageNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたページは見つかりませんでした。'];

            return $this;
        }

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
     * @psalm-taint-source input $pageId
     * @psalm-taint-source input $pageName
     * @psalm-taint-source input $pageUrl
     * @psalm-taint-source input $pageFileName
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goPage', href: 'page://self/admin/page/page')]
    public function onPut(
        string $pageId,
        string|null $pageName = null,
        string|null $pageUrl = null,
        string|null $pageFileName = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdatePageInput(
                pageId: $pageId,
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
        } catch (PageNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたページは見つかりませんでした。'];

            return $this;
        }

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
     * @psalm-taint-source input $pageId
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goPageList', href: 'page://self/admin/page/page-list')]
    public function onDelete(string $pageId, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new DeletePageInput(pageId: $pageId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (PageNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたページは見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof PageDeleted);

        $this->code = Code::OK;
        $this->body = ['pageId' => $final->pageId];

        return $this;
    }
}
