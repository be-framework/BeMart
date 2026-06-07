<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\JsonSchema;

/**
 * EC-CUBE goShoppingShippingMultiple — 複数配送先設定画面 (Wave 3H pure renderer).
 *
 * Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
 * Maps to `page://self/shopping/shipping/multiple`.
 *
 * Production EC-CUBE distributes cart items across multiple shipping
 * addresses (per-item address selection). Wave 3H exposes the shape
 * only; the cart-item × address allocation form is left as TODO
 * (the rt is `#Shopping` per ALPS, so on submit the flow returns to
 * the main shopping screen).
 */
class ShippingMultiple extends ResourceObject
{
    /**
     * @todo Wave-future: enumerate the current cart's items and the
     *     customer's registered addresses, then render the per-item
     *     address-assignment form. Submit target writes a Shipping
     *     allocation back to the pre-order.
     */
    #[Link(rel: 'goShopping', href: 'page://self/shopping')]
    #[Link(rel: 'goShoppingShipping', href: 'page://self/shopping/shipping')]
    // BEAR URL routing: `Resource\Page\Shopping\ShippingMultiple` ↔
    // `page://self/shopping/shipping-multiple` (kebab-case, same
    // convention as Mypage\OrderHistory ↔ /mypage/order-history).
    /** ALPS `goShoppingShippingMultiple` に対応する GET 操作。 */
    #[Alps('goShoppingShippingMultiple')]
    #[JsonSchema(schema: 'get-shopping-shipping-multiple.json')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingShippingMultiple',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => null,
            'links' => [
                'goShopping' => 'page://self/shopping',
                'goShoppingShipping' => 'page://self/shopping/shipping',
            ],
            'cartItems' => [],
            'addresses' => [],
        ];

        return $this;
    }

    /**
     * EC-CUBE doSelectShippingAddress — accept the multi-shipping allocation.
     *
     * The current page exposes the allocation form shape but has no cart-item
     * rows yet. The POST endpoint is still concrete: CSRF is enforced, the
     * transition is acknowledged, and the user returns to the shopping page.
     *
     * @param array<mixed> $allocations
     */
    #[Alps('doSelectShippingAddress')]
    #[JsonSchema(schema: 'post-shopping-shipping-multiple.json', params: 'post-shopping-shipping-multiple.param.json')]
    #[Link(rel: 'goShopping', href: 'page://self/shopping')]
    #[CsrfProtected]
    public function onPost(array $allocations = []): static
    {
        $this->code = Code::SEE_OTHER;
        $this->headers['Location'] = '/shopping';
        $this->body = [
            'transitionId' => 'doSelectShippingAddress',
            'allocationCount' => count($allocations),
            'message' => '複数配送先を選択しました。',
        ];

        return $this;
    }
}
