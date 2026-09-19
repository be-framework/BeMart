<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Ray\Csrf\Attribute\CsrfToken;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ContentCssUpdated;
use MyVendor\BeMart\Be\Input\UpdateContentCssInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Csrf\CsrfTokenInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface;
use MyVendor\BeMart\Form\AdminCssForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE カスタマイズCSS編集 — admin CMS thin renderer (Phase 3 HTML).
 *
 * PORT-side note: EC-CUBE's `CssController` reads / writes a single
 * `customize.css` file on disk; there is no Be domain entity for it (the
 * customize-CSS file was not modelled in any ALPS wave). The Be transition
 * updates the EC-CUBE-compatible asset boundary; GET renders that readback
 * through {@see AdminCssForm}.
 *
 * FLAGGED: a future `be/src/` wave should model the customize-CSS file as
 * a Be domain (Get/Update Inputs + Final) so this resource can carry the
 * public customize.css asset instead of the runtime compatibility boundary.
 */
class Css extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly CustomizeAssetWriterInterface $assetWriter,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /** ALPS `goContentCss` に対応する GET 操作。 */
    #[Alps('goContentCss')]
    #[JsonSchema(schema: 'get-admin-content-css.json')]
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
        $this->body = [
            'form' => $form,
            'csrfToken' => $this->csrf->issue(),
        ];

        return $this;
    }

    /**
     * Saves the customize CSS (doUpdateContentCss). ALPS idempotent → PUT.
     *
     * @psalm-taint-source input $css
     */
    #[Alps('doUpdateContentCss')]
    #[JsonSchema(schema: 'put-admin-content-css.json', params: 'put-admin-content-css.param.json')]
    #[Link(rel: 'goContentJs', href: 'page://self/admin/content/js')]
    #[CsrfToken]
    public function onPut(string $css = '', string|null $mode = null): static
    {
        $final = ($this->becoming)(new UpdateContentCssInput(css: $css));

        assert($final instanceof ContentCssUpdated);

        $this->code = $mode === 'content_operation_form' ? Code::SEE_OTHER : Code::OK;
        $this->headers['Location'] = '/admin/content/css';
        $this->body = [
            'transitionId' => 'doUpdateContentCss',
            'length' => $final->length,
            'message' => 'CSSを更新しました。',
        ];

        return $this;
    }
}
