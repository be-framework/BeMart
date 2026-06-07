<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE doResetPassword の新しいパスワード設定フォーム —
 * Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Front/PasswordResetType` + the
 * `Forgot/reset.twig` `form_widget` calls. EC-CUBE renders the inputs
 * through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule.
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 * field definition + HTML rendering + repopulation only. Validation
 * authority stays with the Be Framework Becoming chain — the
 * ResetPasswordInput Semantics own the password-format rule and the
 * PasswordResetCompleted Final + ResetKeyInvalidException own the
 * reset-key verdict. The `#[FormValidation]` aspect is NOT used.
 *
 * Fields: EC-CUBE's `PasswordResetType` nests the new password under a
 * `RepeatedPasswordType` (`form.password.first` / `.second`); BeMart
 * declares the two leaf inputs flat (`password`, `password_confirm`) the
 * same way {@see EntryForm} does. The `email` field is the
 * confirmation email EC-CUBE's reset screen asks the user to re-enter;
 * BeMart's `ResetPasswordInput` does not model it (the reset is keyed by
 * `resetKey`), so it is declared here purely as a renderer field. The
 * `resetKey` itself is a hidden field carried by the Reset page body.
 *
 * MISSING-FIELD NOTE — `email` has no BeMart body field behind it
 * (ResetPasswordInput models `resetKey` + `password`, not email). Per
 * the Phase-3 recipe a template wave does not enrich the Input/ALPS; the
 * field is rendered for fidelity and flagged as a missing-body-field
 * residual in the render test.
 *
 * @link https://schema.org/Action
 */
final class ResetForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the password-reset form fields.
     */
    #[Override]
    public function init(): void
    {
        // メールアドレス — EC-CUBE's PasswordResetType field name.
        $this->setField('email', 'text');

        // パスワード + 確認 — EC-CUBE's RepeatedPasswordType leaf inputs.
        $this->setField('password', 'password')
            ->setAttribs(['placeholder' => '半角英数記号8〜32文字']);
        $this->setField('password_confirm', 'password')
            ->setAttribs(['placeholder' => '確認のためもう一度入力してください']);

        // NON-AUTHORITATIVE structural checks only — authority is the Be
        // domain (ResetPasswordInput Semantics + PasswordResetCompleted
        // Final).
        $this->filter->validate('email')->isNotBlank();
        $this->filter->validate('password')->isNotBlank();
    }

    /**
     * Repopulates the form inputs with submitted values.
     *
     * The password fields are intentionally NOT repopulated; pass only
     * the safe-to-echo email.
     *
     * @param array<string, string> $values field name => submitted value
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }

    /**
     * Bridges a Be-domain rejection onto the form's error state.
     */
    public function setDomainError(string $field, string $message): void
    {
        $this->domainErrors[$field] = $message;
    }

    /**
     * Returns the error message for a field — Be-domain errors take
     * precedence over the Aura.Filter structural message.
     */
    #[Override]
    public function error(string $input): string
    {
        if (isset($this->domainErrors[$input])) {
            return $this->domainErrors[$input];
        }

        return parent::error($input);
    }
}
