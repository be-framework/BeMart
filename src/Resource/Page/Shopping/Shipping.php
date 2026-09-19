<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\JsonSchema;

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
     * ALPS `goShoppingShipping` に対応する GET 操作。
     * @todo Wave-future: surface the authenticated customer's
     *     registered shipping addresses (name01/name02/postalCode/pref/
     *     addr01/addr02/phoneNumber per ALPS #ShoppingShipping) so the
     *     UI can render the radio-button list. Requires AddressStorage
     *     scoped to the current session/customer.
     */
    #[Alps('goShoppingShipping')]
    #[JsonSchema(schema: 'get-shopping-shipping.json')]
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
            'addresses' => [],
            'csrfToken' => null,
        ];

        return $this;
    }

    /**
     * EC-CUBE doSelectShippingAddress — accept the selected address-book row.
     *
     * This closes the former ActionRedirect gap for the customer checkout
     * route. The current shopping renderer does not yet hydrate the address
     * radio list, so the resource records the selected id in the response
     * surface and returns to the shopping page. The full pre-order shipping
     * persistence is intentionally left to the existing checkout enrichment
     * backlog; this method makes the route executable without a placeholder.
     *
     * @psalm-taint-source input $shippingAddressId
     */
    #[Alps('doSelectShippingAddress')]
    #[JsonSchema(schema: 'post-shopping-shipping.json', params: 'post-shopping-shipping.param.json')]
    #[Link(rel: 'goShopping', href: 'page://self/shopping')]
    #[CsrfToken]
    public function onPost(string|null $shippingAddressId = null): static
    {
        $this->code = Code::SEE_OTHER;
        $this->headers['Location'] = '/shopping';
        $this->body = [
            'transitionId' => 'doSelectShippingAddress',
            'shippingAddressId' => $shippingAddressId,
            'message' => 'お届け先を選択しました。',
        ];

        return $this;
    }
}
