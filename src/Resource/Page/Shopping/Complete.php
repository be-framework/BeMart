<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goShoppingComplete — ご注文完了 (Phase 3 — thin pure renderer).
 *
 * NEW RESOURCE — flagged as a follow-up. EC-CUBE renders the
 * order-complete screen (ALPS `#ShoppingComplete`) after `doCheckout`
 * succeeds. BeMart's `Shopping/Checkout::onPost` (doCheckout) returns
 * the `CheckoutCompleted` projection and sets `Location:
 * /shopping/complete?orderNo=...`, but no resource backed that URL —
 * the complete screen was never rendered as a page. Phase 3 needs a
 * page to render `Shopping/complete.twig` against, so this THIN PURE
 * RENDERER is added: no Be Framework, no domain logic, no Reasons. It
 * exposes the complete-screen shape + the outbound transitions
 * (goTop / goCart) per ALPS `#ShoppingComplete`.
 *
 * FOLLOW-UP — the complete screen displays the freshly-placed order's
 * number + the complete message. EC-CUBE re-fetches the `Order` by id
 * from the request. The honest binding is for `Checkout::onPost` to
 * carry `orderNo` / `completeMessage` into this page's body (the
 * `CheckoutCompleted` Final already produces them). Threading that
 * post-redirect handoff — a flash / order-id transport — is a dedicated
 * slice, tracked in the enrichment backlog. Until then `complete.twig`'s
 * `{% if Order.id %}` order-number block and the `Order.complete_message`
 * block render empty (the body carries no `orderNo`), recorded as a
 * MISSING BODY FIELD residual in the render test.
 *
 * Maps to `page://self/shopping/complete`.
 */
class Complete extends ResourceObject
{
    /**
     * @todo Enrichment backlog: thread the placed order's `orderNo` /
     *     `completeMessage` from `Checkout::onPost` (CheckoutCompleted)
     *     into this page's body so the complete screen shows the real
     *     order number. Requires a post-redirect order-id transport.
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingComplete',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => [
                'page' => 'shopping-complete',
                'title' => 'ご注文完了',
            ],
            'links' => [
                'goTop' => 'page://self/',
                'goCart' => 'page://self/cart',
            ],
        ];

        return $this;
    }
}
