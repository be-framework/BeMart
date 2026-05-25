<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\News;

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
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
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
        private readonly CsrfTokenInterface $csrf,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * @psalm-taint-source input $newsId
     */
    #[Link(rel: 'goNewsList', href: 'page://self/admin/news/news-list')]
    public function onGet(string $newsId): static
    {
        try {
            $final = ($this->becoming)(new GetAdminNewsInput(newsId: $newsId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (NewsNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたニュースは見つかりませんでした。'];

            return $this;
        }

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
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goNews', href: 'page://self/admin/news/news')]
    public function onPut(
        string $newsId,
        string|null $newsTitle = null,
        string|null $newsDescription = null,
        string|null $newsUrl = null,
        string|null $publishDate = null,
        bool|null $linkMethod = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateNewsInput(
                newsId: $newsId,
                newsTitle: $newsTitle,
                newsDescription: $newsDescription,
                newsUrl: $newsUrl,
                publishDate: $publishDate,
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
        } catch (NewsNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたニュースは見つかりませんでした。'];

            return $this;
        }

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
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goNewsList', href: 'page://self/admin/news/news-list')]
    public function onDelete(string $newsId, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new DeleteNewsInput(newsId: $newsId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (NewsNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたニュースは見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof NewsDeleted);

        $this->code = Code::OK;
        $this->body = ['newsId' => $final->newsId];

        return $this;
    }
}
