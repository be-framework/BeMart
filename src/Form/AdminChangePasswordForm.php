<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 管理者パスワード変更フォーム — top-level admin wave, form-page recipe.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/ChangePasswordType` + the
 * `admin/change_password.twig` `form_widget` calls. EC-CUBE renders
 * these inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html) — the same
 * library {@see AdminNewsForm} / {@see AdminLoginForm} adopted.
 *
 * EC-CUBE's `ChangePasswordType` uses a `RepeatedType` for the new
 * password (`change_password.first` / `change_password.second`). Ray's
 * AbstractForm is flat, so the repeated pair is declared as two flat
 * fields `change_password_first` / `change_password_second`. EC-CUBE's
 * block prefix is `admin_change_password`, so the FormView ids are
 * `admin_change_password_current_password`,
 * `admin_change_password_change_password_first` and
 * `admin_change_password_change_password_second` — folded into the
 * field attrs here.
 *
 * VALIDATION AUTHORITY: there is no Be Framework `doChangeAdminPassword`
 * transition (the be/ domain layer is frozen for this wave), so the
 * admin ChangePassword resource is a THIN RENDERER — it exposes this
 * form for the HTML page only. The `#[FormValidation]` aspect is NOT
 * used; the filter carries non-authoritative structural checks only.
 */
final class AdminChangePasswordForm extends AbstractForm
{
    /**
     * Declares the admin change-password form fields.
     *
     * Ported from EC-CUBE's `ChangePasswordType::buildForm()` +
     * `change_password.twig`: `current_password`, `change_password.first`
     * and `change_password.second` — all rendered by the template with
     * `{ type: 'password', value: '' }`.
     */
    #[Override]
    public function init(): void
    {
        $this->setField('current_password', 'password')
            ->setAttribs([
                'id' => 'admin_change_password_current_password',
                'class' => 'form-control',
            ]);

        $this->setField('change_password_first', 'password')
            ->setAttribs([
                'id' => 'admin_change_password_change_password_first',
                'class' => 'form-control',
            ]);

        $this->setField('change_password_second', 'password')
            ->setAttribs([
                'id' => 'admin_change_password_change_password_second',
                'class' => 'form-control',
            ]);

        // NON-AUTHORITATIVE structural checks only. The authoritative
        // password rules (current-password match, length, pattern) are
        // EC-CUBE-domain; no Be transition exists for this wave.
        $this->filter->validate('current_password')->isNotBlank();
        $this->filter->validate('change_password_first')->isNotBlank();
        $this->filter->validate('change_password_second')->isNotBlank();
    }
}
