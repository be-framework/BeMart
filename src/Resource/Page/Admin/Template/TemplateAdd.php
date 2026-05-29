<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Template;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\TemplateInstalled;
use MyVendor\BeMart\Be\Input\InstallTemplateInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminTemplateAddForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE テンプレート登録 — Store Tier-2 (`admin/Store/template_add.twig`).
 *
 *   GET /admin/store/template/add → template-upload screen
 *
 * Thin GET renderer for EC-CUBE's design-template registration screen:
 * a template code, a template name and a zip-archive file-upload form.
 * The matching `doTemplateInstall` write transition is a Phase-A stub —
 * this port renders the upload shell only, mirroring the Product
 * CSV-upload Tier-2 wave ({@see \MyVendor\BeMart\Resource\Page\Admin\Product\AbstractCsvUpload}).
 *
 * AUTHZ is a direct admin-session check (Pattern B — no Be transition is
 * invoked on the GET path; an anonymous admin → 403). The form renders
 * blank against empty JSON-backed fake storage — no storage is seeded.
 */
class TemplateAdd extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'goTemplateList', href: 'page://self/admin/template/template-list')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminTemplateAddForm::class);
        assert($form instanceof AdminTemplateAddForm);

        $this->code = Code::OK;
        $this->body = ['form' => $form];

        return $this;
    }

    /**
     * Installs an uploaded design template (doInstallTemplate). ALPS
     * marks it `unsafe` → POST.
     *
     * @psalm-taint-source input $templateCode
     * @psalm-taint-source input $templateName
     */
    #[CsrfProtected]
    #[Link(rel: 'goTemplateList', href: 'page://self/admin/template/template-list')]
    public function onPost(string $templateCode, string $templateName): static
    {
        try {
            $final = ($this->becoming)(new InstallTemplateInput(
                templateCode: $templateCode,
                templateName: $templateName,
            ));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof TemplateInstalled);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin_store_template';
        $this->body = [
            'transitionId' => 'doInstallTemplate',
            'templateId' => $final->templateId,
            'message' => 'テンプレートを追加しました。',
        ];

        return $this;
    }
}
