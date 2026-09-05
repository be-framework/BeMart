<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 管理者2段階認証フォーム — top-level admin wave, form-page recipe.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/TwoFactorAuthType` + the
 * `admin/two_factor_auth.twig` / `admin/two_factor_auth_set.twig`
 * `form_widget` calls. EC-CUBE renders these inputs through the Symfony
 * FormView; BeMart renders them through Ray.WebFormModule (Aura.Input +
 * Aura.Filter + Aura.Html).
 *
 * One form serves both login-context 2FA pages:
 *  - `two_factor_auth.twig`     — the challenge: renders `deviceToken`.
 *  - `two_factor_auth_set.twig` — device registration: renders
 *    `deviceToken` + the hidden `authKey`.
 *
 * EC-CUBE's block prefix is `admin_two_factor_auth`, so the FormView
 * ids are `admin_two_factor_auth_device_token` /
 * `admin_two_factor_auth_auth_key` — folded into the field attrs here.
 * `two_factor_auth.twig` passes its own attrs to the `device_token`
 * widget; that template-side override is folded in.
 *
 * VALIDATION AUTHORITY: there is no Be Framework 2FA transition (the
 * be/ domain layer is frozen for this wave), so the 2FA resources are
 * THIN RENDERERS — they expose this form for the HTML pages only. The
 * `#[FormValidation]` aspect is NOT used; the filter carries
 * non-authoritative structural checks only (the authoritative
 * 6-digit-token rule is EC-CUBE-domain).
 */
final class AdminTwoFactorAuthForm extends AbstractForm
{
    /**
     * Declares the 2FA form fields.
     *
     * Ported from EC-CUBE's `TwoFactorAuthType::buildForm()`:
     * `deviceToken` (text, maxlength 6) and `authKey` (hidden). The
     * token widget declares `inputmode`/`pattern` so phones open the
     * numeric keypad; `two_factor_auth_set` uses the same definition
     * (one form, two pages).
     */
    #[Override]
    public function init(): void
    {
        $this->setField('deviceToken', 'text')
            ->setAttribs([
                'id' => 'admin_two_factor_auth_device_token',
                'class' => 'form-control text-center',
                'maxlength' => '6',
                'inputmode' => 'numeric',
                'pattern' => '\\d{6}',
                'placeholder' => '123456',
                'autofocus' => 'autofocus',
            ]);

        $this->setField('authKey', 'hidden')
            ->setAttribs([
                'id' => 'admin_two_factor_auth_auth_key',
            ]);

        // NON-AUTHORITATIVE structural check only. The authoritative
        // 6-digit numeric token rule is EC-CUBE-domain; no Be transition
        // exists for this wave.
        $this->filter->validate('deviceToken')->isNotBlank();
    }

    /**
     * Repopulates hidden server-side challenge fields for HTML rendering.
     *
     * @param array<string, string> $values field name => value
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
