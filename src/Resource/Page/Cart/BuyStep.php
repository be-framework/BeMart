<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Cart;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE cart_buystep — select the cart to buy and continue checkout.
 *
 * EC-CUBE marks the selected sale-type cart as primary before redirecting to
 * the shopping flow. BeMart currently keeps one HTML cart partition per
 * session, so this endpoint records the requested cart key in the response and
 * redirects to Shopping without using the generic ActionRedirect fallback.
 */
class BuyStep extends ResourceObject
{
    /**
     * @psalm-taint-source input $cartKey
     */
    #[Link(rel: 'goShopping', href: 'page://self/shopping')]
    public function onGet(string $cartKey): static
    {
        $this->code = Code::OK;
        $this->headers['Location'] = '/shopping';
        $this->body = [
            'transitionId' => 'doSelectCartForCheckout',
            'cartKey' => $cartKey,
            'message' => '購入手続きへ進みます。',
        ];

        return $this;
    }
}
