<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE doRequestPasswordReset のパスワード再発行依頼フォーム —
 * Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Front/ForgotType` + the
 * `Forgot/index.twig` `form_widget(form.email)` call. EC-CUBE
 * renders the single email input through the Symfony FormView; BeMart
 * renders it through Ray.WebFormModule.
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 * field definition + HTML rendering + repopulation only. Validation
 * authority stays with the Be Framework Becoming chain — the
 * RequestPasswordResetInput Semantics own the email-format rule, and the
 * password-reset request is anti-enumeration (uniform 200 / uniform
 * message). The `#[FormValidation]` aspect is NOT used.
 *
 * EC-CUBE's `ForgotType` uses the field name `email`; this form
 * declares the same name so the rendered `<input>` markup matches.
 *
 * @link https://schema.org/Action
 */
final class ForgotForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the password-reset-request form field.
     */
    #[Override]
    public function init(): void
    {
        // メールアドレス — EC-CUBE's ForgotType field name is `email`.
        $this->setField('email', 'text');

        // NON-AUTHORITATIVE structural check only — authority is the Be
        // domain (RequestPasswordResetInput Semantics).
        $this->filter->validate('email')->isNotBlank();
    }

    /**
     * Repopulates the form input with the submitted value.
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
