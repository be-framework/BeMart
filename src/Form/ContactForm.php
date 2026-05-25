<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE goContactForm のお問い合わせフォーム — Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Front/ContactType` + the
 * `Contact/index.twig` `form_widget` calls. EC-CUBE renders these inputs
 * through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html).
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 *
 *  - **Field definition** — `init()` declares the contact inputs with the
 *    EC-CUBE field names so the rendered `<input>` / `<textarea>` markup
 *    reproduces EC-CUBE's `ec-*` form. BeMart's Contact resource body
 *    carries the fields FLAT (`contactName01`, `contactEmail`, ...) — see
 *    Contact::onGet — so the form declares them flat to match.
 *  - **HTML rendering** — `{{ form.input('contactEmail') }}` in
 *    `Contact.html.twig`.
 *  - **Repopulation** — after a failed POST {@see fillValues()}.
 *
 * MISSING-FIELD NOTE — EC-CUBE's `ContactType` collects MORE fields than
 * BeMart's `SubmitContactInput` / the ALPS `ContactForm` descriptor
 * model: EC-CUBE has kana / postal_code / pref / addr01 / addr02 /
 * phone_number; BeMart models only name01/02 + email + contents. Per the
 * Phase-3 recipe a template wave does NOT enrich the Input/ALPS — this
 * form declares ONLY the four modelled fields, and the kana / address /
 * phone <dl> rows are enumerated as a missing-body-field residual in the
 * render test. Flagged for a follow-up vertical slice.
 *
 * What this class deliberately does NOT do — **validation authority**:
 * the authoritative format/required rules live in the Be domain
 * (SubmitContactInput Semantics + ContactSubmitted Final). The filter
 * here carries only NON-AUTHORITATIVE structural checks. The
 * `#[FormValidation]` aspect is NOT used.
 *
 * @link https://schema.org/ContactPage
 */
final class ContactForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the contact form fields.
     *
     * Only the four fields BeMart's `SubmitContactInput` models are
     * declared — see the class-level missing-field note.
     */
    #[Override]
    public function init(): void
    {
        // お名前 — half-width name pair.
        $this->setField('contactName01', 'text')->setAttribs(['placeholder' => '姓']);
        $this->setField('contactName02', 'text')->setAttribs(['placeholder' => '名']);

        // メールアドレス.
        $this->setField('contactEmail', 'text');

        // お問い合わせ内容.
        $this->setField('contactContents', 'textarea');

        // NON-AUTHORITATIVE structural checks only — authority is the Be
        // domain (SubmitContactInput Semantics + ContactSubmitted Final).
        $this->filter->validate('contactName01')->isNotBlank();
        $this->filter->validate('contactName02')->isNotBlank();
        $this->filter->validate('contactEmail')->isNotBlank();
        $this->filter->validate('contactContents')->isNotBlank();
    }

    /**
     * Repopulates the form inputs with submitted values.
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
