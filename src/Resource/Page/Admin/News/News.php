<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\News;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\NewsNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminNewsFetched;
use MyVendor\BeMart\Be\Final\NewsDeleted;
use MyVendor\BeMart\Be\Final\NewsUpdated;
use MyVendor\BeMart\Be\Input\DeleteNewsInput;
use MyVendor\BeMart\Be\Input\GetAdminNewsInput;
use MyVendor\BeMart\Be\Input\UpdateNewsInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\AdminNewsForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE goNews + doUpdateNews + doDeleteNews — single-row endpoint
 * (Wave 9).
 *
 * Phase 3 — HTML FORM page (admin pilot). `onGet` exposes an
 * {@see AdminNewsForm} (Ray.WebFormModule AbstractForm) as `body['form']`
 * pre-filled with the persisted row so the admin edit page can render
 * real `<input>`s via `{{ form.input(...) }}`. The form is a
 * field-definition + renderer only — VALIDATION AUTHORITY STAYS WITH the
 * Be Framework Becoming chain. The JSON contexts (`app`, `prod`, `test`)
 * ignore `body['form']`; the resource tests assert key-wise on `body`
 * and are unaffected. FormFactory is self-sufficient (no Ray.Di bindings
 * needed).
 */
class News extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrf,
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * @psalm-taint-source input $newsId
     */
    #[Link(rel: 'goNewsList', href: 'page://self/admin/news/news-list')]
    public function onGet(string|null $newsId = null): static
    {
        if ($newsId === null || $newsId === '') {
            if ($this->adminSession->adminId === null) {
                $this->code = Code::FORBIDDEN;
                $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

                return $this;
            }

            $this->code = Code::OK;
            $this->body = [
                'newsId' => '',
                'newsTitle' => '',
                'newsDescription' => '',
                'newsUrl' => '',
                'publishDate' => '2026-05-23 00:00:00',
                'linkMethod' => false,
                'csrfToken' => $this->csrf->token,
            ];
            $this->body['form'] = $this->editForm($this->body);

            return $this;
        }

        $final = ($this->becoming)(new GetAdminNewsInput(newsId: $newsId));

        assert($final instanceof AdminNewsFetched);

        $this->code = Code::OK;
        $this->body = [
            'newsId' => $final->newsId,
            'newsTitle' => $final->newsTitle,
            'newsDescription' => $final->newsDescription,
            'newsUrl' => $final->newsUrl,
            'publishDate' => $final->publishDate,
            'linkMethod' => $final->linkMethod,
        ];
        // Phase 3: an AdminNewsForm pre-filled with the persisted row,
        // for the HTML edit page to render via `{{ form.input(...) }}`.
        // JSON contexts ignore it.
        $this->body['form'] = $this->editForm($this->body);

        return $this;
    }

    /**
     * Builds an AdminNewsForm filled from a News body.
     *
     * The Becoming chain reached the data; this only loads it onto the
     * form so the HTML edit page renders the persisted values. The form
     * is a renderer here, never a validator.
     *
     * @param array<string, mixed> $body
     */
    private function editForm(array $body): AdminNewsForm
    {
        $form = $this->formFactory->newInstance(AdminNewsForm::class);
        assert($form instanceof AdminNewsForm);
        $form->fillValues($body);

        return $form;
    }

    /**
     * @psalm-taint-source input $newsId
     * @psalm-taint-source input $newsTitle
     * @psalm-taint-source input $newsDescription
     * @psalm-taint-source input $newsUrl
     * @psalm-taint-source input $publishDate
     * @psalm-taint-source input $linkMethod
     */
    #[Link(rel: 'goNews', href: 'page://self/admin/news/news')]
    #[CsrfProtected]
    public function onPut(
        string $newsId,
        string|null $newsTitle = null,
        string|null $newsDescription = null,
        string|null $newsUrl = null,
        string|null $publishDate = null,
        bool|null $linkMethod = null,
    ): static {
        $final = ($this->becoming)(new UpdateNewsInput(
            newsId: $newsId,
            newsTitle: $newsTitle,
            newsDescription: $newsDescription,
            newsUrl: $newsUrl,
            publishDate: $publishDate,
            linkMethod: $linkMethod,
        ));

        assert($final instanceof NewsUpdated);

        $this->code = Code::OK;
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

    /**
     * @psalm-taint-source input $newsId
     */
    #[Link(rel: 'goNewsList', href: 'page://self/admin/news/news-list')]
    #[Link(rel: 'goPageList', href: 'page://self/admin/page/page-list')]
    #[CsrfProtected]
    public function onDelete(string $newsId): static
    {
        $final = ($this->becoming)(new DeleteNewsInput(newsId: $newsId));

        assert($final instanceof NewsDeleted);

        $this->code = Code::OK;
        $this->body = ['newsId' => $final->newsId];

        return $this;
    }
}
