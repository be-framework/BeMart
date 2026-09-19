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
use MyVendor\BeMart\Be\Final\ContentJsUpdated;
use MyVendor\BeMart\Be\Input\UpdateContentJsInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Csrf\CsrfTokenInterface;
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
 * customize-JS file was not modelled in any ALPS wave). The Be transition
 * updates the EC-CUBE-compatible asset boundary; GET renders that readback
 * through {@see AdminJsForm}.
 *
 * FLAGGED: a future `be/src/` wave should model the customize-JS file as
 * a Be domain so this resource can write the public customize.js asset
 * instead of the runtime compatibility boundary.
 */
class Js extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly CustomizeAssetWriterInterface $assetWriter,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /** ALPS `goContentJs` に対応する GET 操作。 */
    #[Alps('goContentJs')]
    #[JsonSchema(schema: 'get-admin-content-js.json')]
    #[Link(rel: 'doUpdateContentJs', href: 'page://self/admin/content/js', method: 'put')]
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
        $this->body = [
            'form' => $form,
            'csrfToken' => $this->csrf->issue(),
        ];

        return $this;
    }

    /**
     * Saves the customize JS (doUpdateContentJs). ALPS idempotent → PUT.
     *
     * @psalm-taint-source input $js
     */
    #[Alps('doUpdateContentJs')]
    #[JsonSchema(schema: 'put-admin-content-js.json', params: 'put-admin-content-js.param.json')]
    #[CsrfToken]
    public function onPut(string $js = '', string|null $mode = null): static
    {
        $final = ($this->becoming)(new UpdateContentJsInput(js: $js));

        assert($final instanceof ContentJsUpdated);

        $this->code = $mode === 'content_operation_form' ? Code::SEE_OTHER : Code::OK;
        $this->headers['Location'] = '/admin/content/js';
        $this->body = [
            'transitionId' => 'doUpdateContentJs',
            'length' => $final->length,
            'message' => 'JavaScriptを更新しました。',
        ];

        return $this;
    }
}
