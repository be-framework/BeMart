<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Layout;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\LayoutNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\LayoutUpdated;
use MyVendor\BeMart\Be\Input\UpdateLayoutInput;
use MyVendor\BeMart\Form\AdminLayoutForm;
use Ray\WebFormModule\FormFactory;

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
 * NOTE — single-row prefill: the Be domain exposes no
 * `GetAdminLayoutInput` / `AdminLayoutFetched` (single-row fetch), so
 * `onGet` renders the NEW-layout form (the `admin_content_layout_new`
 * case — the layout designer with an empty block canvas). Pre-filling an
 * existing layout + its block positions would need a Be fetch Input — a
 * `be/src/` change out of this Phase 3 HTML wave's scope. FLAGGED:
 * follow-up to add `GetAdminLayoutInput` for existing-layout edit prefill.
 */
class Layout extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * Renders the layout editor form (new-layout case).
     *
     * The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.
     */
    #[Link(rel: 'goLayoutList', href: 'page://self/admin/layout/layout-list')]
    public function onGet(): static
    {
        $form = $this->formFactory->newInstance(AdminLayoutForm::class);
        assert($form instanceof AdminLayoutForm);

        $this->code = Code::OK;
        $this->body = ['form' => $form];

        return $this;
    }

    /**
     * @psalm-taint-source input $layoutId
     * @psalm-taint-source input $layoutName
     */
    #[Link(rel: 'goLayoutList', href: 'page://self/admin/layout/layout-list')]
    #[CsrfProtected]
    public function onPut(
        string $layoutId,
        string|null $layoutName = null,
    ): static {
        try {
            $final = ($this->becoming)(new UpdateLayoutInput(
                layoutId: $layoutId,
                layoutName: $layoutName,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (LayoutNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたレイアウトは見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof LayoutUpdated);

        $this->code = Code::OK;
        $this->body = [
            'layoutId' => $final->layoutId,
            'layoutName' => $final->layoutName,
            'deviceType' => $final->deviceType,
        ];

        return $this;
    }
}
