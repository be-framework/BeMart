<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminJsForm;
use Ray\WebFormModule\FormFactory;

use function assert;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_string;

/**
 * EC-CUBE カスタマイズJavaScript編集 — admin CMS thin renderer
 * (Phase 3 HTML).
 *
 * PORT-side note: EC-CUBE's `JsController` reads / writes a single
 * `customize.js` file on disk; there is no Be domain entity for it. This
 * resource is therefore a THIN HTML RENDERER only — it carries no
 * `be/src/` Becoming chain. It authenticates at the resource layer via
 * {@see AdminSession} and exposes {@see AdminJsForm} populated from the
 * real `customize.js` file for the `Content/js.twig` port to render via
 * `{{ form.input('js') }}`.
 */
class Js extends ResourceObject
{
    private readonly string $customizeJsPath;

    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        string|null $customizeJsPath = null,
    ) {
        $this->customizeJsPath = $customizeJsPath ?? dirname(__DIR__, 5) . '/public/assets/js/customize.js';
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
        $js = $this->readJs();
        $form->fillValues(['js' => $js]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'js' => $js,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $js
     */
    #[CsrfProtected]
    public function onPost(string $js = ''): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        if (file_put_contents($this->customizeJsPath, $js) === false) {
            $this->code = Code::ERROR;
            $this->body = ['message' => 'JavaScriptファイルを更新できませんでした。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminJsForm::class);
        assert($form instanceof AdminJsForm);
        $form->fillValues(['js' => $js]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'js' => $js,
            'message' => 'JavaScriptを更新しました。',
        ];

        return $this;
    }

    private function readJs(): string
    {
        $js = file_get_contents($this->customizeJsPath);

        return is_string($js) ? $js : '';
    }
}
