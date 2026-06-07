<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\Annotation\JsonSchema;

/**
 * EC-CUBE goShoppingError — 購入エラー表示 (Wave 3H pure renderer).
 *
 * Pure static page: no Be Framework, no domain logic, no Reasons.
 * Anonymous-or-authenticated (the checkout flow lands here regardless
 * of the originating identity). Maps to `page://self/shopping/error`.
 *
 * In production this page is hit by redirect from doConfirmOrder /
 * doCheckout when stock / payment / session checks fail. Wave 3H
 * renders the surface only — the actual error reason is not threaded
 * through here yet (production EC-CUBE puts the message in a flashbag).
 *
 * The ALPS `#ShoppingError` resource declares a single outbound
 * transition: goCart.
 */
class Error extends ResourceObject
{
    /**
     * ALPS `goShoppingError` に対応する GET 操作。
     * @todo Wave-future: surface the underlying error reason (stock /
     *     payment / session) once the flashbag-equivalent transport
     *     is decided.
     */
    #[Alps('goShoppingError')]
    #[JsonSchema(schema: 'get-shopping-error.json')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingError',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => [
                'page' => 'shopping-error',
                'title' => '購入エラー',
                'sections' => [],
            ],
            'links' => [
                'goCart' => 'page://self/cart',
            ],
        ];

        return $this;
    }
}
