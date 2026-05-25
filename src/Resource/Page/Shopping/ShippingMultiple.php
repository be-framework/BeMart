<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

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
}
