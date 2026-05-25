<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminTwoFactorAuthForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE 管理者2段階認証設定 — Setting/System Tier-2.
 *
 * Admin-authenticated variant of the top-level 2FA setup renderer. The
 * underlying TOTP verification is not an ALPS transition in this repo,
 * so the resource serves the GET page and form body only.
 */
class TwoFactorAuthEdit extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    public function onGet(): static
    {
        $adminId = $this->adminSession->adminId;
        if ($adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminTwoFactorAuthForm::class);
        assert($form instanceof AdminTwoFactorAuthForm);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'authKey' => '',
            'memberName' => $adminId,
            'shopName' => 'BeMart',
        ];

        return $this;
    }
}
