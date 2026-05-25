<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminSecurityForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE セキュリティ管理 — Setting/System Tier-2.
 *
 * Thin GET renderer for `Setting/System/security.twig`. BeMart does not
 * yet model config-file writes as Be transitions, so this page exposes
 * the current default config body shape for HTML rendering only.
 */
class Security extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminSecurityForm::class);
        assert($form instanceof AdminSecurityForm);
        $form->fillValues([
            'admin_route_dir' => 'admin',
            'admin_allow_hosts' => '',
            'admin_deny_hosts' => '',
            'front_allow_hosts' => '',
            'front_deny_hosts' => '',
            'trusted_hosts' => '^localhost$',
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'isSecureRequest' => false,
        ];

        return $this;
    }
}
