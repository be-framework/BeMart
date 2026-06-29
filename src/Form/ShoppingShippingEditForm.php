<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE goShoppingShippingEdit のお届け先変更フォーム —
 * Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Front/CustomerAddressType` + the
 * `Shopping/shipping_edit.twig` `form_widget` calls. Identical field
 * shape to {@see AddressForm} (the mypage address-book editor) — the
 * checkout-flow shipping-address editor reuses the same Symfony form
 * type — but kept as a distinct class so the two pages can diverge
 * later (the checkout editor's submit target / repopulation source are
 * the pre-order's shipping selection, not the customer address book).
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 *
 *  - **Field definition** — `init()` declares the shipping-address
 *    inputs with the EC-CUBE field names / placeholders. EC-CUBE's
 *    `CustomerAddressType` nests fields under compound types
 *    (`form.name.name01`, `form.address.pref`), but BeMart's ShippingEdit
 *    resource body carries the fields FLAT (mirrors ALPS
 *    `#ShoppingShippingEdit`), so the form declares them flat.
 *  - **HTML rendering** — `{{ form.input('name01') }}` in
 *    `Page/Shopping/ShippingEdit.html.twig`.
 *  - **Repopulation** — {@see fillValues()} pre-populates the editor
 *    with the currently-selected shipping address (a Wave-future TODO on
 *    the resource) and re-shows submitted values after a rejection.
 *
 * What this class deliberately does NOT do — **validation authority**:
 *
 *   BeMart validates the address in the domain via Be Framework
 *   Semantics and the Final/exception layer. Duplicating those
 *   ALPS-derived rules into Aura.Filter would drift from the spec, so
 *   the filter here carries only NON-AUTHORITATIVE structural checks.
 *   The `#[FormValidation]` aspect is NOT used.
 *
 * @link https://schema.org/PostalAddress
 */
final class ShoppingShippingEditForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the shipping-address form fields.
     *
     * Field names / placeholders are ported from EC-CUBE's
     * `CustomerAddressType` leaf fields + `Shopping/shipping_edit.twig`'s
     * `form_widget` `attr` options. `pref` is a `<select>` whose option
     * set is EC-CUBE master data (`mtb_pref`) the resource body does not
     * carry — it renders as the bare empty control, the option set an
     * EC-CUBE-runtime residual.
     */
    #[Override]
    public function init(): void
    {
        // お名前 / お名前(カナ).
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
        // domain.
        $this->filter->validate('name01')->isNotBlank();
        $this->filter->validate('name02')->isNotBlank();
        $this->filter->validate('postalCode')->isNotBlank();
        $this->filter->validate('addr01')->isNotBlank();
    }

    /**
     * Repopulates the form inputs with values.
     *
     * @param array<string, scalar|null> $values field name => value
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
