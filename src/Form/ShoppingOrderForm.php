<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE goShopping (注文情報入力) の注文フォーム — Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Shopping/OrderType` + the
 * `Shopping/index.twig` `form_widget` calls. EC-CUBE renders these
 * inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html).
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 *
 *  - **Field definition** — `init()` declares the checkout-page inputs
 *    with the EC-CUBE field names / attributes so the rendered markup
 *    reproduces EC-CUBE's `ec-orderRole` form. EC-CUBE's `OrderType`
 *    nests the per-shipping delivery selects under a `Shippings`
 *    `CollectionType` and the payment radios under a `Payment`
 *    `EntityType`; BeMart's Shopping resource body carries a single
 *    default shipping address + a flat `paymentMethods` list, so this
 *    form declares the delivery selects FLAT (`delivery`,
 *    `shipping_delivery_date`, `delivery_time`) and a single `payment`
 *    radio group — the shape a single-shipping order needs.
 *  - **HTML rendering** — `{{ form.input('message') }}` /
 *    `{{ form.input('payment') }}` in `Page/Shopping.html.twig`.
 *  - **Repopulation** — {@see fillValues()} re-shows submitted values
 *    after a Be-domain rejection.
 *
 * SCOPE — the delivery-method / delivery-date / delivery-time
 * `<select>` option sets and the payment-method `<radio>` option set
 * are EC-CUBE master data (`Delivery` / `DeliveryTime` entities, the
 * `mtb`-style date list). BeMart's Shopping body carries the
 * user-selectable `paymentMethods` list but no Delivery aggregation, so
 * the delivery selects render as the bare empty control and the
 * `payment` radio's options are populated by the template from
 * `body.paymentMethods`. The Delivery option sets are flagged as a
 * MISSING BODY FIELD follow-up (a Delivery / DeliveryTime aggregation).
 *
 * What this class deliberately does NOT do — **validation authority**:
 *
 *   BeMart validates the order in the domain via Be Framework Semantics
 *   (CheckoutInput) and the Final/exception layer. Duplicating those
 *   ALPS-derived rules into Aura.Filter would drift from the spec, so the
 *   filter here carries only NON-AUTHORITATIVE structural checks. The
 *   `#[FormValidation]` aspect is NOT used.
 *
 * @link https://schema.org/OrderAction
 */
final class ShoppingOrderForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the checkout-page form fields.
     *
     * Field names / attributes are ported from EC-CUBE's `OrderType`
     * leaf fields + `Shopping/index.twig`'s `form_widget` `attr` options:
     *   - `message` — a 6-row textarea (`TextareaType`), the お問い合わせ
     *     free-text area.
     *   - `delivery` / `shipping_delivery_date` / `delivery_time` —
     *     `<select>` widgets carrying `form-control` (EC-CUBE master
     *     data; rendered bare-empty, see SCOPE).
     *   - `payment` — the payment-method `<radio>` group; its options
     *     are populated by the template from `body.paymentMethods`.
     */
    #[Override]
    public function init(): void
    {
        // お問い合わせ — 6-row textarea.
        $this->setField('message', 'textarea')->setAttribs([
            'class' => 'form-control',
            'placeholder' => 'お問い合わせ事項がございましたら、こちらにご入力ください。(3000文字まで)',
            'rows' => '6',
        ]);

        // 配送方法 / お届け日 / お届け時間 — EC-CUBE master-data selects.
        $this->setField('delivery', 'select')
            ->setAttribs(['class' => 'form-control'])
            ->setOptions([]);
        $this->setField('shipping_delivery_date', 'select')
            ->setAttribs(['class' => 'form-control'])
            ->setOptions([]);
        $this->setField('delivery_time', 'select')
            ->setAttribs(['class' => 'form-control'])
            ->setOptions([]);

        // お支払方法 — radio group; options come from body.paymentMethods.
        $this->setField('payment', 'radio')->setOptions([]);

        // NON-AUTHORITATIVE structural check only — authority is the Be
        // domain (CheckoutInput Semantics).
        $this->filter->validate('payment')->isNotBlank();
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
