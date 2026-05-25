<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goShoppingConfirm — 注文内容のご確認 (Phase 3 — thin pure renderer).
 *
 * NEW RESOURCE — flagged as a follow-up. EC-CUBE's checkout flow has a
 * `doConfirmOrder` → `ShoppingConfirm` screen (ALPS `#ShoppingConfirm`)
 * between `goShopping` and `doCheckout`: the order-summary review page
 * the customer confirms before the order is committed. BeMart's Pilot 5
 * collapsed the flow — `Shopping::onGet` (review) hands straight to
 * `Shopping/Checkout::onPost` (doCheckout) — so no `ShoppingConfirm`
 * resource existed. Phase 3 needs a page to render `Shopping/confirm.twig`
 * against, so this THIN PURE RENDERER is added: no Be Framework, no
 * domain logic, no Reasons. It exposes only the confirm-screen shape +
 * the outbound transitions (doCheckout / goShoppingError) per ALPS
 * `#ShoppingConfirm`.
 *
 * FOLLOW-UP — the confirm screen's body should carry the aggregated
 * order projection (the same Order shape `confirm.twig` reads:
 * shippings / orderItems / payment / the tax-rate-broken-down totals).
 * Wiring `doConfirmOrder` into the Be Becoming chain — a real
 * PurchaseFlow-equivalent aggregation that produces an `OrderConfirmed`
 * Final — is a dedicated vertical-slice, tracked in the enrichment
 * backlog. Until then `confirm.twig`'s order-detail loops render empty
 * (the `Order.shippings` / `Order.order_items` body fields are absent),
 * recorded as MISSING BODY FIELD residuals in the render test.
 *
 * Maps to `page://self/shopping/confirm`. The submit target is
 * doCheckout (`page://self/shopping/checkout`).
 */
class Confirm extends ResourceObject
{
    /**
     * @todo Enrichment backlog: surface the aggregated order projection
     *     (shippings / orderItems / payment / totals broken down by tax
     *     rate) so the confirm screen renders the real order summary.
     *     Requires a `doConfirmOrder` Be Becoming chain producing an
     *     `OrderConfirmed` Final.
     */
    #[Link(rel: 'doCheckout', href: 'page://self/shopping/checkout', method: 'post')]
    #[Link(rel: 'goShoppingError', href: 'page://self/shopping/error')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingConfirm',
            'fields' => ['csrfToken'],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/shopping/checkout',
            ],
            'staticContent' => null,
            'links' => [
                'doCheckout' => 'page://self/shopping/checkout',
                'goShoppingError' => 'page://self/shopping/error',
            ],
            'csrfToken' => null,
        ];

        return $this;
    }
}
