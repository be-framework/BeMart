<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Layout;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\LayoutNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminLayoutFetched;
use MyVendor\BeMart\Be\Final\LayoutUpdated;
use MyVendor\BeMart\Be\Input\GetAdminLayoutInput;
use MyVendor\BeMart\Be\Input\UpdateLayoutInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\AdminLayoutForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doUpdateLayout — single-row endpoint (Wave 9 CMS). Only PUT
 * is exposed to the domain; layouts can be neither created nor deleted
 * via the admin UI (system-managed).
 *
 * Phase 3 — HTML FORM page. `onGet` exposes an {@see AdminLayoutForm}
 * (Ray.WebFormModule AbstractForm) as `body['form']` so the admin layout
 * editor (`Content/layout.twig` port) can render the real layout-name
 * `<input>` via `{{ form.input(...) }}`.
 *
 * `onGet` without a `layoutId` still renders the new-layout form. With a
 * `layoutId`, it opens the existing layout row and prefills the editable
 * name while the block-position designer remains a residual placeholder.
 */
class Layout extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
        private readonly AdminSession $adminSession,
        private readonly CsrfToken $csrf,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * Renders the layout editor form (new-layout case).
     *
     * The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.
     */
    #[Alps('goLayout')]
    #[JsonSchema(schema: 'get-admin-layout-layout.json', params: 'get-admin-layout-layout.param.json')]
    #[Link(rel: 'goLayoutList', href: 'page://self/admin/layout/layout-list')]
    #[Link(rel: 'doUpdateLayout', href: 'page://self/admin/layout/layout', method: 'put')]
    public function onGet(string|null $layoutId = null): static
    {
        $form = $this->formFactory->newInstance(AdminLayoutForm::class);
        assert($form instanceof AdminLayoutForm);
        $body = [
            'layoutId' => $layoutId,
            'layoutName' => '',
            'deviceType' => null,
            'csrfToken' => $this->csrf->token,
        ];

        if ($layoutId === null || $layoutId === '') {
            if ($this->adminSession->adminId === null) {
                throw new UnauthorizedAdminAccessException();
            }
        } else {
            $final = ($this->becoming)(new GetAdminLayoutInput(layoutId: $layoutId));
            assert($final instanceof AdminLayoutFetched);
            $body = [
                'layoutId' => $final->layoutId,
                'layoutName' => $final->layoutName,
                'deviceType' => $final->deviceType,
                'csrfToken' => $this->csrf->token,
            ];
        }

        $form->fillValues($body);

        $this->code = Code::OK;
        $this->body = $body + ['form' => $form];

        return $this;
    }

    /**
     * ALPS `doUpdateLayout` に対応する PUT 操作。
     * @psalm-taint-source input $layoutId
     * @psalm-taint-source input $layoutName
     */
    #[Alps('doUpdateLayout')]
    #[JsonSchema(schema: 'put-admin-layout-layout.json', params: 'put-admin-layout-layout.param.json')]
    #[Link(rel: 'goLayoutList', href: 'page://self/admin/layout/layout-list')]
    #[Link(rel: 'goTradeLawList', href: 'page://self/admin/trade-law')]
    #[CsrfProtected]
    public function onPut(
        string $layoutId,
        string|null $layoutName = null,
        string|null $name = null,
    ): static {
        $layoutName ??= $name;
        $final = ($this->becoming)(new UpdateLayoutInput(
            layoutId: $layoutId,
            layoutName: $layoutName,
        ));

        assert($final instanceof LayoutUpdated);

        ($this->mutationResponse)($this, Code::OK);
        $this->headers['Location'] = '/admin/layout/layout-list';
        $this->body = [
            'layoutId' => $final->layoutId,
            'layoutName' => $final->layoutName,
            'deviceType' => $final->deviceType,
        ];

        return $this;
    }
}
