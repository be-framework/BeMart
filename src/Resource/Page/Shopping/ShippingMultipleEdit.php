<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Form\ShoppingShippingEditForm;
use Ray\WebFormModule\FormFactory;

/**
 * EC-CUBE goShoppingShippingMultipleEdit — 複数配送の新規お届け先追加フォーム
 * (Phase 3 — thin pure renderer).
 *
 * NEW RESOURCE — flagged as a follow-up. EC-CUBE reaches
 * `Shopping/shipping_multiple_edit.twig` from the multi-destination
 * screen ({@see ShippingMultiple}) via the "新規お届け先を追加する"
 * link; it adds a shipping address that the multi-destination split UI
 * can then assign cart items to. On submit the flow returns to the
 * multi-destination screen.
 *
 * BeMart's ALPS surface models the multi-destination allocation as a
 * Wave-future vertical-slice (see {@see ShippingMultiple}), so no
 * `ShoppingShippingMultipleEdit` SCREEN resource ever existed. Phase 3
 * needs a page to render `Shopping/shipping_multiple_edit.twig` against,
 * so this THIN PURE RENDERER is added: no Be Framework, no domain logic,
 * no Reasons.
 *
 * `Shopping/shipping_multiple_edit.twig` is a FORM page — EC-CUBE renders
 * its address inputs through the Symfony FormView (the SAME
 * `CustomerAddressType` used by `Shopping/shipping_edit.twig`). The form
 * shape is therefore identical to {@see ShippingEdit}'s, so this resource
 * reuses {@see ShoppingShippingEditForm} (an AbstractForm) — exposed as
 * `body['form']` so the HTML port renders real `<input>`s via
 * `{{ form.input(...) }}`. JSON contexts ignore `body['form']`. The two
 * pages differ only in the submit-target route + the page header text;
 * the address form definition itself is shared.
 *
 * Maps to `page://self/shopping/shipping-multiple-edit`.
 */
class ShippingMultipleEdit extends ResourceObject
{
    public function __construct(
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * @todo Wave-future: on submit, append the entered address to the
     *     pre-order's shipping set so the multi-destination split screen
     *     can assign cart items to it.
     */
    #[Link(rel: 'doAddMultipleShippingAddress', href: 'page://self/shopping/shipping-multiple-edit', method: 'post')]
    #[Link(rel: 'goShoppingShippingMultiple', href: 'page://self/shopping/shipping-multiple')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingShippingMultipleEdit',
            'fields' => [
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
                'csrfToken',
            ],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/shopping/shipping-multiple-edit',
            ],
            'staticContent' => null,
            'links' => [
                'doAddMultipleShippingAddress' => 'page://self/shopping/shipping-multiple-edit',
                'goShoppingShippingMultiple' => 'page://self/shopping/shipping-multiple',
            ],
            'csrfToken' => null,
            // Phase 3: an empty ShoppingShippingEditForm (the shared
            // CustomerAddressType shape) for the HTML port to render the
            // address inputs. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(ShoppingShippingEditForm::class),
        ];

        return $this;
    }
}
