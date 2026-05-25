<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE goCustomerRegistrationConfirm の会員登録確認フォーム
 * — Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Entry/confirm.twig`'s `form_widget(form.<field>,
 * { type : 'hidden' })` calls. The registration-confirm screen re-shows
 * the entered values as plain text AND carries them forward as HIDDEN
 * inputs so the final "会員登録をする" submit re-posts the full
 * registration payload to `doRegisterCustomer`.
 *
 * Why a dedicated confirm form rather than reusing {@see EntryForm}
 * directly: EC-CUBE's confirm.twig renders the SAME registration fields
 * but overrides the widget type to `hidden` at the `form_widget` call
 * site (`{ type : 'hidden' }`). Ray.WebFormModule's `AbstractForm::input`
 * has no per-call type override — the field type is fixed in `init()`.
 * This class is therefore the form-page recipe applied to the confirm
 * screen: it declares the EXACT SAME field names as {@see EntryForm} but
 * with the `hidden` type, so `{{ form.input('name01') }}` emits
 * `<input type="hidden" name="name01" ...>`. It is the registration form
 * re-expressed for the confirm step, not a new field set.
 *
 * Validation authority is unchanged — the confirm screen submits back to
 * `Entry::onPost`, where the Be Framework Becoming chain is the single
 * source of truth. This form is a hidden-field renderer + repopulation
 * carrier only; it declares no Aura.Filter rules.
 *
 * @see EntryForm  the registration-entry form this mirrors
 */
final class EntryConfirmForm extends AbstractForm
{
    /**
     * Declares the registration fields as HIDDEN inputs.
     *
     * Field names mirror {@see EntryForm::init()} exactly so the confirm
     * screen's hidden payload matches the registration form's input set.
     */
    #[Override]
    public function init(): void
    {
        foreach ([
            'name01',
            'name02',
            'kana01',
            'kana02',
            'companyName',
            'postalCode',
            'pref',
            'addr01',
            'addr02',
            'phoneNumber',
            'email',
            'email_confirm',
            'password',
            'password_confirm',
            'birth_year',
            'birth_month',
            'birth_day',
            'sex',
            'job',
            'user_policy_check',
        ] as $field) {
            $this->setField($field, 'hidden');
        }
    }

    /**
     * Repopulates the hidden inputs with the submitted values so the
     * confirm screen carries the registration payload forward.
     *
     * @param array<string, string> $values field name => submitted value
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
