<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminCssForm;
use Ray\WebFormModule\FormFactory;

use function assert;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_string;

/**
 * EC-CUBE カスタマイズCSS編集 — admin CMS thin renderer (Phase 3 HTML).
 *
 * PORT-side note: EC-CUBE's `CssController` reads / writes a single
 * `customize.css` file on disk; there is no Be domain entity for it (the
 * customize-CSS file was not modelled in any ALPS wave). This resource is
 * therefore a THIN HTML RENDERER only — it carries no `be/src/` Becoming
 * chain. It authenticates at the resource layer via
 * {@see AdminSession} (the same guard the Be CMS Finals apply)
 * and exposes {@see AdminCssForm} populated from the real `customize.css`
 * file for the `Content/css.twig` port to render via
 * `{{ form.input('css') }}`.
 */
class Css extends ResourceObject
{
    private readonly string $customizeCssPath;

    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        string|null $customizeCssPath = null,
    ) {
        $this->customizeCssPath = $customizeCssPath ?? dirname(__DIR__, 5) . '/public/assets/css/customize.css';
    }

    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminCssForm::class);
        assert($form instanceof AdminCssForm);
        $css = $this->readCss();
        $form->fillValues(['css' => $css]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'css' => $css,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $css
     */
    #[CsrfProtected]
    public function onPost(string $css = ''): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        if (file_put_contents($this->customizeCssPath, $css) === false) {
            $this->code = Code::ERROR;
            $this->body = ['message' => 'CSSファイルを更新できませんでした。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminCssForm::class);
        assert($form instanceof AdminCssForm);
        $form->fillValues(['css' => $css]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'css' => $css,
            'message' => 'CSSを更新しました。',
        ];

        return $this;
    }

    private function readCss(): string
    {
        $css = file_get_contents($this->customizeCssPath);

        return is_string($css) ? $css : '';
    }
}
