<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ContentCssUpdated;
use MyVendor\BeMart\Be\Input\UpdateContentCssInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface;
use MyVendor\BeMart\Form\AdminCssForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE カスタマイズCSS編集 — admin CMS thin renderer (Phase 3 HTML).
 *
 * PORT-side note: EC-CUBE's `CssController` reads / writes a single
 * `customize.css` file on disk; there is no Be domain entity for it (the
 * customize-CSS file was not modelled in any ALPS wave). This resource is
 * therefore a THIN HTML RENDERER only — it carries no `be/src/` Becoming
 * chain. It authenticates at the resource layer via
 * {@see AdminSession} (the same guard the Be CMS Finals apply)
 * and exposes an empty {@see AdminCssForm} for the
 * `Content/css.twig` port to render via `{{ form.input('css') }}`.
 *
 * FLAGGED: a future `be/src/` wave should model the customize-CSS file as
 * a Be domain (Get/Update Inputs + Final) so this resource can carry the
 * real persisted CSS instead of an empty editor.
 */
class Css extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly CustomizeAssetWriterInterface $assetWriter,
    ) {
    }

    #[Link(rel: 'doUpdateContentCss', href: 'page://self/admin/content/css', method: 'put')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminCssForm::class);
        assert($form instanceof AdminCssForm);
        $form->fillValues(['css' => $this->assetWriter->readCss()]);

        $this->code = Code::OK;
        $this->body = ['form' => $form];

        return $this;
    }

    /**
     * Saves the customize CSS (doUpdateContentCss). ALPS idempotent → PUT.
     *
     * @psalm-taint-source input $css
     */
    #[Link(rel: 'goContentJs', href: 'page://self/admin/content/js')]
    #[CsrfProtected]
    public function onPut(string $css = ''): static
    {
        try {
            $final = ($this->becoming)(new UpdateContentCssInput(css: $css));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof ContentCssUpdated);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin_content_css';
        $this->body = [
            'transitionId' => 'doUpdateContentCss',
            'length' => $final->length,
            'message' => 'CSSを更新しました。',
        ];

        return $this;
    }
}
