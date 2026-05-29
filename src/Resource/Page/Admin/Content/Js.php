<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ContentJsUpdated;
use MyVendor\BeMart\Be\Input\UpdateContentJsInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface;
use MyVendor\BeMart\Form\AdminJsForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE カスタマイズJavaScript編集 — admin CMS thin renderer
 * (Phase 3 HTML).
 *
 * PORT-side note: EC-CUBE's `JsController` reads / writes a single
 * `customize.js` file on disk; there is no Be domain entity for it. This
 * resource is therefore a THIN HTML RENDERER only — it carries no
 * `be/src/` Becoming chain. It authenticates at the resource layer via
 * {@see AdminSession} and exposes an empty {@see AdminJsForm}
 * for the `Content/js.twig` port to render via `{{ form.input('js') }}`.
 *
 * FLAGGED: a future `be/src/` wave should model the customize-JS file as
 * a Be domain so this resource can carry the real persisted JS.
 */
class Js extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly CustomizeAssetWriterInterface $assetWriter,
    ) {
    }

    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminJsForm::class);
        assert($form instanceof AdminJsForm);
        $form->fillValues(['js' => $this->assetWriter->readJs()]);

        $this->code = Code::OK;
        $this->body = ['form' => $form];

        return $this;
    }

    /**
     * Saves the customize JS (doUpdateContentJs). ALPS idempotent → PUT.
     *
     * @psalm-taint-source input $js
     */
    #[CsrfProtected]
    public function onPut(string $js = ''): static
    {
        try {
            $final = ($this->becoming)(new UpdateContentJsInput(js: $js));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof ContentJsUpdated);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin_content_js';
        $this->body = [
            'transitionId' => 'doUpdateContentJs',
            'length' => $final->length,
            'message' => 'JavaScriptを更新しました。',
        ];

        return $this;
    }
}
