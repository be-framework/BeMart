<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
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
 * {@see AdminSessionInterface} and exposes an empty {@see AdminJsForm}
 * for the `Content/js.twig` port to render via `{{ form.input('js') }}`.
 *
 * FLAGGED: a future `be/src/` wave should model the customize-JS file as
 * a Be domain so this resource can carry the real persisted JS.
 */
class Js extends ResourceObject
{
    public function __construct(
        private readonly AdminSessionInterface $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    public function onGet(): static
    {
        if ($this->adminSession->adminId() === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminJsForm::class);
        assert($form instanceof AdminJsForm);

        $this->code = Code::OK;
        $this->body = ['form' => $form];

        return $this;
    }
}
