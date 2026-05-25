<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 商品詳細のカート追加フォーム — Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/AddCartType` + the `Product/detail.twig`
 * `form_widget(form.quantity)` call. EC-CUBE renders the add-cart inputs
 * through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html).
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 *
 *  - **Field definition** — `init()` declares the add-cart inputs with
 *    the EC-CUBE field names / attributes (`quantity` IntegerType with
 *    `min` / `maxlength`, the `product_id` hidden) so the rendered
 *    `<input>` markup reproduces EC-CUBE's `ec-numberInput` shape.
 *  - **HTML rendering** — `{{ form.input('quantity')|raw }}` in
 *    `Product.html.twig`.
 *  - **Repopulation** — {@see fillValues()} so a POST that the Be domain
 *    rejects re-shows the entered quantity, and the hidden `product_id`
 *    can be seeded with the current product code.
 *
 * SCOPE — EC-CUBE's `AddCartType` also builds `classcategory_id1` /
 * `classcategory_id2` `ChoiceType` selects and a hidden `ProductClass`,
 * but ONLY when the product has product classes (`getClassName1()` etc.).
 * BeMart's Product resource body is the Grade-C 厳密移植 projection
 * (`productCode` / `productName` / `price02` / `stock`) — it carries no
 * ProductClass join — so the class-selection family is genuinely absent
 * from BeMart's data. EC-CUBE's `detail.twig` guards every
 * `classcategory_id*` block behind `{% if form.classcategory_idN is
 * defined %}`; with no class data those branches do not render on either
 * side. This form therefore declares only `product_id` + `quantity`,
 * matching a class-less product. The class-selection inputs are flagged
 * as a MISSING BODY FIELD follow-up — a Product vertical-slice
 * enrichment (ProductClass aggregation) is needed before they can be
 * ported.
 *
 * What this class deliberately does NOT do — **validation authority**:
 *
 *   BeMart validates the add-cart action in the domain via Be Framework
 *   Semantics (the Cart `AddCartItemInput` / `#quantity` Semantic) and
 *   the Final/exception layer. Those ALPS-derived rules are the single
 *   source of truth. Duplicating them into Aura.Filter would drift from
 *   the spec, so the filter here carries only NON-AUTHORITATIVE
 *   structural checks. The `#[FormValidation]` aspect is NOT used.
 *
 * @link https://schema.org/AddAction
 */
final class AddCartForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the add-cart form fields.
     *
     * Field names / attributes are ported from EC-CUBE's `AddCartType`:
     *   - `product_id` — a hidden input carrying the product identity.
     *     EC-CUBE seeds it with `Product->getId()`; BeMart seeds it with
     *     the product code via {@see fillValues()}.
     *   - `quantity` — an integer input, `data: 1`, `attr: {min: 1,
     *     maxlength: 9}` (`eccube_int_len`). Rendered inside the
     *     `ec-numberInput` block.
     */
    #[Override]
    public function init(): void
    {
        $this->setField('product_id', 'hidden');
        $this->setField('csrfToken', 'hidden');

        $this->setField('quantity', 'number')->setAttribs([
            'id' => 'quantity',
            'min' => '1',
            'maxlength' => '9',
            'value' => '1',
        ]);

        // NON-AUTHORITATIVE structural check only — authority is the Be
        // domain (the Cart add-item Input + `#quantity` Semantic).
        $this->filter->validate('quantity')->isNotBlank();
    }

    /**
     * Repopulates the form inputs with values.
     *
     * Used to seed the hidden `product_id` with the current product code
     * on `onGet`, and to re-show the submitted quantity after a Be-domain
     * rejection.
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
