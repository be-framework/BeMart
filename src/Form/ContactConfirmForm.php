<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE goContactConfirm のお問い合わせ確認フォーム — Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Contact/confirm.twig`'s `form_widget(form.<field>,
 * { type : 'hidden' })` calls. The inquiry-confirm screen re-shows the
 * submitted inquiry as plain text AND carries it forward as HIDDEN
 * inputs so the final "送信する" submit re-posts the full inquiry to
 * `doSubmitContact`.
 *
 * Like {@see EntryConfirmForm}, this is the form-page recipe applied to
 * the confirm step: it declares the EXACT SAME field names as
 * {@see ContactForm} but with the `hidden` type — Ray.WebFormModule's
 * `AbstractForm::input` has no per-call type override, so the `hidden`
 * type is fixed in `init()`. It is the contact form re-expressed for the
 * confirm step, not a new field set.
 *
 * Validation authority is unchanged — the confirm screen submits back to
 * `Contact::onPost`, where the Be Framework Becoming chain is the single
 * source of truth. This form is a hidden-field renderer + repopulation
 * carrier only; it declares no Aura.Filter rules.
 *
 * MISSING-FIELD NOTE — as with {@see ContactForm}, only the four fields
 * BeMart's `SubmitContactInput` models are declared. EC-CUBE's confirm
 * screen also re-shows kana / address / phone; those rows are OMITTED
 * from the port (never invented) and enumerated as a missing-body-field
 * residual in the render test. Flagged for a follow-up vertical slice.
 *
 * @see ContactForm  the inquiry-entry form this mirrors
 */
final class ContactConfirmForm extends AbstractForm
{
    /**
     * Declares the inquiry fields as HIDDEN inputs.
     *
     * Field names mirror {@see ContactForm::init()} exactly.
     */
    #[Override]
    public function init(): void
    {
        foreach ([
            'contactName01',
            'contactName02',
            'contactEmail',
            'contactContents',
        ] as $field) {
            $this->setField($field, 'hidden');
        }
    }

    /**
     * Repopulates the hidden inputs with the submitted values.
     *
     * @param array<string, string> $values field name => submitted value
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
