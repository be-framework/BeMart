<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 管理者ログインフォーム — top-level admin wave, form-page recipe.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/LoginType` + the
 * `admin/login.twig` `form_widget` calls. EC-CUBE renders these inputs
 * through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html) — the same
 * library the storefront's {@see LoginForm} pilot and the admin
 * {@see AdminNewsForm} adopted.
 *
 * This is the admin login-context form page. The recipe is identical to
 * the storefront form-page recipe (see var/templates/README.md): the
 * form is a FIELD-DEFINITION + RENDERER only — VALIDATION AUTHORITY
 * STAYS WITH the Be Framework Becoming chain (the admin Login resource
 * hands the raw input to Becoming, then bridges any rejection back onto
 * this form). The `#[FormValidation]` aspect is NOT used.
 *
 * Field names / ids are ported from EC-CUBE's `LoginType` (block prefix
 * `admin_login`, so the FormView ids are `admin_login_<field>`). The
 * `login.twig` template overrides `login_id`'s id to a bare `login_id`
 * and its placeholder; those overrides are folded into the field attrs
 * here so the rendered `<input>` markup matches EC-CUBE's.
 */
final class AdminLoginForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name. Consulted by {@see error()} so a field error renders
     * the Be-domain message, not an Aura.Filter message.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the admin login form fields.
     *
     * Ported verbatim from EC-CUBE's `LoginType::buildForm()` +
     * `login.twig`: `login_id` (text, maxlength 50 — `eccube_id_max_len`)
     * and `password` (password, maxlength 50 — `eccube_password_max_len`).
     * `login.twig` passes `{'id': 'login_id', 'attr': {'placeholder':
     * 'admin.login.login_id', 'autofocus': true}}` to the `login_id`
     * widget and `{'attr': {'placeholder': 'admin.login.password'}}` to
     * the `password` widget — those template-side overrides are folded
     * in here.
     */
    #[Override]
    public function init(): void
    {
        $this->setField('login_id', 'text')
            ->setAttribs([
                'id' => 'login_id',
                'class' => 'form-control',
                'maxlength' => '50',
                'placeholder' => 'ログインID',
                'autofocus' => 'autofocus',
            ]);

        $this->setField('password', 'password')
            ->setAttribs([
                'id' => 'admin_login_password',
                'class' => 'form-control',
                'maxlength' => '50',
                'placeholder' => 'パスワード',
            ]);

        // NON-AUTHORITATIVE structural checks only. The authoritative
        // loginId / password rules live in the Be domain (AdminLoginInput
        // Semantics + AdminAuthenticated Final). The Login resource does
        // not consult this filter.
        $this->filter->validate('login_id')->isNotBlank();
        $this->filter->validate('password')->isNotBlank();
    }

    /**
     * Repopulates the form inputs with submitted values.
     *
     * Called by the admin Login resource after a failed POST so the page
     * re-renders with the entered login id — EC-CUBE's
     * `_security.last_username` behaviour. The password is intentionally
     * NOT repopulated.
     *
     * @param array<string, string> $values field name => submitted value
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }

    /**
     * Bridges a Be-domain rejection onto the form's error state.
     *
     * The admin Login resource calls this when the Becoming chain rejects
     * the credentials. The message then surfaces through
     * `{{ form.error(field) }}`. Validation authority stays with Be —
     * this only transports a verdict the domain already reached.
     */
    public function setDomainError(string $field, string $message): void
    {
        $this->domainErrors[$field] = $message;
    }

    /**
     * Returns the error message for a field.
     *
     * Be-domain errors (bridged via {@see setDomainError()}) take
     * precedence — they are the authoritative verdict. Falls back to the
     * Aura.Filter structural message only if no domain error is present.
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
