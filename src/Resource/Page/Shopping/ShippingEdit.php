<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

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
 */
class ShippingEdit extends ResourceObject
{
    /**
     * @todo Wave-future: prefill the form fields with the currently
     *     selected shipping address (member's chosen address book entry
     *     OR the non-member's submitted address).
     */
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
        ];

        return $this;
    }
}
