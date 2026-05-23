<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Template;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Form\AdminTemplateAddForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE Store/template_add.twig — テンプレートアップロード form.
 *
 * Admin Tier-2 thin GET renderer. EC-CUBE's actual template install
 * pipeline validates and expands an archive; BeMart does not yet expose
 * that domain transition, so this resource renders the upload form only.
 */
class TemplateAdd extends ResourceObject
{
    public function __construct(
        private readonly AdminSessionInterface $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    #[Link(rel: 'goTemplateList', href: 'page://self/admin/template/template-list')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId() === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminTemplateAddForm::class);
        assert($form instanceof AdminTemplateAddForm);

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goAdminTemplateAdd',
            'fields' => ['code', 'name', 'file'],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/admin/template/template-add',
            ],
            'links' => [
                'goTemplateList' => 'page://self/admin/template/template-list',
            ],
            'form' => $form,
        ];

        return $this;
    }
}
