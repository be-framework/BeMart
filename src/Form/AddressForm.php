<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE お届け先情報編集フォーム — Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Front/CustomerAddressType` + the
 * `Mypage/delivery_edit.twig` `form_widget` calls. EC-CUBE renders these
 * inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html).
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 *
 *  - **Field definition** — `init()` declares the address-book inputs
 *    with the EC-CUBE field names / placeholders so the rendered
 *    `<input>` / `<select>` markup reproduces EC-CUBE's `ec-*` form.
 *    EC-CUBE's `CustomerAddressType` nests fields under compound types
 *    (`form.name.name01`, `form.address.pref`), but BeMart's Address
 *    resource body carries the fields FLAT (`name01`, `pref`, ...), so
 *    the form declares them flat to match the resource. The names are
 *    the same leaf names EC-CUBE renders.
 *  - **HTML rendering** — `{{ form.input('name01') }}` in
 *    `Address.html.twig`.
 *  - **Repopulation** — for the edit screen the resource calls
 *    {@see fillValues()} so the page renders pre-populated with the
 *    existing address; after a failed POST the same path repopulates
 *    the submitted values.
 *
 * What this class deliberately does NOT do — **validation authority**:
 *
 *   BeMart validates the address in the domain via Be Framework
 *   Semantics (CreateCustomerAddressInput / UpdateCustomerAddressInput)
 *   and the Final/exception layer. Those ALPS-derived rules are the
 *   single source of truth. Duplicating them into Aura.Filter would
 *   drift from the spec, so the filter here carries only
 *   NON-AUTHORITATIVE structural checks. The `#[FormValidation]` aspect
 *   is NOT used.
 *
 * @link https://schema.org/PostalAddress
 */
final class AddressForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the address-book form fields.
     *
     * Field names / placeholders are ported from EC-CUBE's
     * `CustomerAddressType` leaf fields + `Mypage/delivery_edit.twig`'s
     * `form_widget` `attr` options so the rendered markup carries
     * EC-CUBE's `ec-*` form shape. `pref` is a `<select>` whose option
     * set is EC-CUBE master data (`mtb_pref`) the resource body does not
     * carry — it renders as the bare empty control, the option set an
     * EC-CUBE-runtime residual.
     */
    #[Override]
    public function init(): void
    {
        // お名前 / お名前(カナ) — half-width name pairs.
        $this->setField('name01', 'text')->setAttribs(['placeholder' => '姓']);
        $this->setField('name02', 'text')->setAttribs(['placeholder' => '名']);
        $this->setField('kana01', 'text')->setAttribs(['placeholder' => 'セイ']);
        $this->setField('kana02', 'text')->setAttribs(['placeholder' => 'メイ']);

        // 会社名 (optional).
        $this->setField('companyName', 'text');

        // 住所 — postal code + prefecture select + address lines.
        $this->setField('postalCode', 'text');
        $this->setField('pref', 'select')->setOptions([]);
        $this->setField('addr01', 'text')
            ->setAttribs(['placeholder' => '市区町村名(例：千代田区)']);
        $this->setField('addr02', 'text')
            ->setAttribs(['placeholder' => '番地・ビル名(例：神田1-1-1)']);

        // 電話番号.
        $this->setField('phoneNumber', 'text');

        // NON-AUTHORITATIVE structural checks only — authority is the Be
        // domain (CreateCustomerAddressInput / UpdateCustomerAddressInput
        // Semantics).
        $this->filter->validate('name01')->isNotBlank();
        $this->filter->validate('name02')->isNotBlank();
        $this->filter->validate('postalCode')->isNotBlank();
        $this->filter->validate('addr01')->isNotBlank();
    }

    /**
     * Repopulates the form inputs with values.
     *
     * Used both to pre-populate the edit screen with the existing
     * address and to re-show submitted values after a failed POST.
     *
     * @param array<string, scalar|null> $values field name => value
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }

    /**
     * Bridges a Be-domain rejection onto the form's error state.
     *
     * Validation authority stays with Be — this method only transports
     * the verdict.
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
