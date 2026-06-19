<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use Ray\Csrf\Attribute\CsrfToken;
use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\InvalidCurrentPasswordException;
use MyVendor\BeMart\Be\Exception\PasswordConfirmationMismatchException;
use MyVendor\BeMart\Be\Exception\PasswordPolicyViolationException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminPasswordChanged;
use MyVendor\BeMart\Be\Input\ChangeAdminPasswordInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminChangePasswordForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

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
 * Hard ActionRedirect completion: `onPost` now drives the Be
 * `doChangePassword` transition ({@see ChangeAdminPasswordInput} →
 * {@see AdminPasswordChanged}) — current-password verification +
 * re-hash over the admin storage, with the credential/CSRF/session
 * boundary enforced Be/BEAR-side.
 */
class ChangePassword extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly MutationResponseInterface $mutationResponse,
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
    #[Alps('doChangePassword')]
    #[JsonSchema(schema: 'get-admin-change-password.json')]
    #[Link(rel: 'goAdminHome', href: 'page://self/admin/index')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
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

    /**
     * Applies the admin's own password change (doChangePassword).
     *
     * Failure mapping:
     *   - Invalid CSRF                         → 403 (interceptor)
     *   - SemanticVariableException            → 400
     *   - InvalidCurrentPasswordException      → 400
     *   - PasswordConfirmationMismatchException→ 400
     *   - PasswordPolicyViolationException     → 400
     *   - UnauthorizedAdminAccessException     → 403 (no admin session)
     *   - AdminNotFoundException               → 404 (stale session)
     *
     * @psalm-taint-source input $currentPassword
     * @psalm-taint-source input $changePasswordFirst
     * @psalm-taint-source input $changePasswordSecond
     */
    #[Alps('doChangePassword')]
    #[JsonSchema(schema: 'post-admin-change-password.json', params: 'post-admin-change-password.param.json')]
    #[CsrfToken]
    #[Link(rel: 'goAdminHome', href: 'page://self/admin/index')]
    public function onPost(
        string $currentPassword,
        string $changePasswordFirst,
        string $changePasswordSecond,
    ): static {
        $final = ($this->becoming)(new ChangeAdminPasswordInput(
            currentPassword: $currentPassword,
            changePasswordFirst: $changePasswordFirst,
            changePasswordSecond: $changePasswordSecond,
        ));

        assert($final instanceof AdminPasswordChanged);

        ($this->mutationResponse)($this, Code::OK, '/admin/change-password');
        $this->body = [
            'transitionId' => 'doChangePassword',
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'message' => 'パスワードを変更しました。',
        ];

        return $this;
    }
}
