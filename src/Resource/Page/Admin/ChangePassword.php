<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Form\AdminChangePasswordForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE admin パスワード変更 — top-level wave, Phase 3.
 *
 * Thin renderer for the forced/voluntary admin password-change screen
 * (`admin/change_password.twig`). EC-CUBE's controller validates the
 * current password and applies the new one via the Symfony security
 * password hasher. There is no Be Framework `doChangeAdminPassword`
 * transition (no such id in `alps.json`, and the be/ domain layer is
 * frozen for this wave), so this resource is a THIN RENDERER: it
 * enforces the admin firewall and exposes an
 * {@see AdminChangePasswordForm} as `body['form']` for the HTML page to
 * render via `{{ form.input(...) }}`.
 *
 * MISSING-BODY-FIELD / domain follow-up (flagged, NOT implemented —
 * the brief freezes be/): the actual password update needs a Be
 * `doChangeAdminPassword` transition (current-password verification +
 * re-hash over the admin storage). `onPost` is intentionally NOT
 * implemented here; adding it requires the be/ domain layer.
 */
class ChangePassword extends ResourceObject
{
    public function __construct(
        private readonly AdminSessionInterface $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * Renders the admin password-change form.
     *
     * Admin-only: returns 403 for an anonymous request — the same
     * firewall contract as the other admin pages, enforced at the
     * resource layer (there is no Be Final to raise
     * `UnauthorizedAdminAccessException`).
     */
    #[Link(rel: 'goAdminHome', href: 'page://self/admin/index')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId() === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goAdminChangePassword',
            'fields' => ['current_password', 'change_password_first', 'change_password_second'],
            // Phase 3: an empty AdminChangePasswordForm for the HTML port
            // to render via `{{ form.input(...) }}`. JSON contexts ignore
            // it.
            'form' => $this->formFactory->newInstance(AdminChangePasswordForm::class),
        ];
        assert($this->body['form'] instanceof AdminChangePasswordForm);

        return $this;
    }
}
