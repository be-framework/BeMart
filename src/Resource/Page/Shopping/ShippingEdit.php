<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Form\ShoppingShippingEditForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

/**
 * EC-CUBE goShoppingShippingEdit — お届け先変更フォーム (Wave 3H pure renderer).
 *
 * Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
 * Maps to `page://self/shopping/shipping/edit`. Submit target is
 * doUpdateShippingAddress.
 *
 * Fields mirror ALPS `#ShoppingShippingEdit`: a guest-shipping-style
 * address form (10 fields). Production EC-CUBE prepopulates with the
 * current shipping selection; Wave 3H exposes the empty form shape
 * only — prefill is left as TODO.
 *
 * Phase 3 — HTML FORM page. `Shopping/shipping_edit.twig` renders the
 * address inputs through the Symfony FormView; BeMart exposes a {@see
 * ShoppingShippingEditForm} (Ray.WebFormModule AbstractForm) as
 * `body['form']` so the HTML port renders real `<input>`s via
 * `{{ form.input(...) }}`. JSON contexts ignore `body['form']`.
 */
class ShippingEdit extends ResourceObject
{
    public function __construct(
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * ALPS `goShoppingShippingEdit` に対応する GET 操作。
     * @todo Wave-future: prefill the form fields with the currently
     *     selected shipping address (member's chosen address book entry
     *     OR the non-member's submitted address).
     */
    #[Alps('goShoppingShippingEdit')]
    #[JsonSchema(schema: 'get-shopping-shipping-edit.json')]
    #[Link(rel: 'doUpdateShippingAddress', href: 'page://self/shopping/shipping-edit', method: 'post')]
    #[Link(rel: 'goShoppingShipping', href: 'page://self/shopping/shipping')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingShippingEdit',
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
                'href' => 'page://self/shopping/shipping-edit',
            ],
            'staticContent' => null,
            'links' => [
                'doUpdateShippingAddress' => 'page://self/shopping/shipping-edit',
                'goShoppingShipping' => 'page://self/shopping/shipping',
            ],
            'csrfToken' => null,
            // Phase 3: an empty ShoppingShippingEditForm for the HTML
            // port to render the address inputs. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(ShoppingShippingEditForm::class),
        ];

        return $this;
    }

    /**
     * EC-CUBE doUpdateShippingAddress — accept the edited shipping address.
     *
     * The BeMart checkout page still keeps the richer pre-order shipping
     * persistence as a later enrichment. This method removes the former
     * ActionRedirect placeholder and gives the submitted address a concrete
     * Resource surface while returning the user to the main shopping page.
     *
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $companyName
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $phoneNumber
     */
    #[Alps('doUpdateShippingAddress')]
    #[JsonSchema(schema: 'post-shopping-shipping-edit.json', params: 'post-shopping-shipping-edit.param.json')]
    #[Link(rel: 'goShopping', href: 'page://self/shopping')]
    #[CsrfProtected]
    public function onPost(
        string $name01 = '',
        string $name02 = '',
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
        string $postalCode = '',
        int $pref = 0,
        string $addr01 = '',
        string $addr02 = '',
        string $phoneNumber = '',
            string|null $csrfToken = null,
    ): static {
        $this->code = Code::SEE_OTHER;
        $this->headers['Location'] = '/shopping';
        $this->body = [
            'transitionId' => 'doUpdateShippingAddress',
            'name01' => $name01,
            'name02' => $name02,
            'kana01' => $kana01,
            'kana02' => $kana02,
            'companyName' => $companyName,
            'postalCode' => $postalCode,
            'pref' => $pref,
            'addr01' => $addr01,
            'addr02' => $addr02,
            'phoneNumber' => $phoneNumber,
            'message' => 'お届け先を更新しました。',
        ];

        return $this;
    }
}
