<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goShoppingShipping — お届け先選択画面 (Wave 3H pure renderer).
 *
 * Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
 * Maps to `page://self/shopping/shipping`. The submit target is
 * doSelectShippingAddress.
 *
 * Production EC-CUBE populates the body with the authenticated
 * customer's registered shipping address list. Wave 3H exposes the
 * shape only; the data lookup (customer's address book under the
 * active pre-order) is left as TODO until a dedicated aggregation
 * lands — the renderer is intentionally anonymous-permissive (matches
 * other Shopping/* renderers under the Wave 3H scope).
 */
class Shipping extends ResourceObject
{
    /**
     * @todo Wave-future: surface the authenticated customer's
     *     registered shipping addresses (name01/name02/postalCode/pref/
     *     addr01/addr02/phoneNumber per ALPS #ShoppingShipping) so the
     *     UI can render the radio-button list. Requires AddressStorage
     *     scoped to the current session/customer.
     */
    #[Link(rel: 'doSelectShippingAddress', href: 'page://self/shopping/shipping', method: 'post')]
    #[Link(rel: 'goShoppingShippingEdit', href: 'page://self/shopping/shipping-edit')]
    #[Link(rel: 'goShoppingShippingMultiple', href: 'page://self/shopping/shipping-multiple')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingShipping',
            'fields' => ['shippingAddressId', 'csrfToken'],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/shopping/shipping',
            ],
            'staticContent' => null,
            'links' => [
                'doSelectShippingAddress' => 'page://self/shopping/shipping',
                'goShoppingShippingEdit' => 'page://self/shopping/shipping-edit',
                'goShoppingShippingMultiple' => 'page://self/shopping/shipping-multiple',
            ],
            'addresses' => [],
            'csrfToken' => null,
        ];

        return $this;
    }
}
