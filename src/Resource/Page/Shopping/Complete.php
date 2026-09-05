<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goShoppingComplete — ご注文完了 (Phase 3 — thin renderer).
 *
 * EC-CUBE renders the order-complete screen (ALPS `#ShoppingComplete`)
 * after `doCheckout` succeeds. BeMart's `Shopping/Checkout::onPost`
 * (doCheckout) returns the `CheckoutCompleted` projection and sets
 * `Location: /shopping/complete?orderNo=...`; this resource backs that
 * URL and renders `Shopping/complete.twig`.
 *
 * Phase 3 enrichment — the complete screen displays the freshly-placed
 * order's number (`#orderNo`) and the per-order complete message
 * (`#completeMessage`), the two data descriptors ALPS `#ShoppingComplete`
 * carries beyond the `goTop` / `goCart` transitions. EC-CUBE re-fetches
 * the `Order` row by id from the request; BeMart mirrors that: the
 * post-checkout redirect carries `orderNo` as a query parameter, and the
 * resource resolves the finalized-order header through the order-header
 * resource's `byOrderNo` read (the same NEW(1)-onwards row
 * `CheckoutCompleted` registered) - now through `app://self/order/header`, which this resource
 * embeds rather than querying itself. The body then carries `orderNo` so the screen shows the
 * real order number.
 *
 * `completeMessage` is intentionally empty — EC-CUBE lets payment
 * plugins append to it via `appendCompleteMessage()`, but the finalized
 * order header carries no such field (CheckoutCompleted produces
 * an empty string in Pilot 5 — a future Plugin Pilot wires it up). The
 * body surfaces it as a `''` default so the template's
 * complete-message block degrades to empty, matching EC-CUBE's
 * plugin-less render.
 *
 * No Be Framework chain — the screen is a pure read of an
 * already-finalized order, no domain transition. An unknown `orderNo`
 * (or none supplied — a direct visit to the URL) still renders the
 * thank-you screen; the order-number block simply stays empty.
 *
 * Maps to `page://self/shopping/complete`.
 */
class Complete extends ResourceObject
{
    /**
     * ALPS `goShoppingComplete` に対応する GET 操作。
     * @psalm-taint-source input $orderNo
     */
    #[Alps('goShoppingComplete')]
    #[JsonSchema(schema: 'get-shopping-complete.json', params: 'get-shopping-complete.param.json')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    #[Embed(rel: 'orderSource', src: 'app://self/order/header{?orderNo}')]
    public function onGet(string $orderNo = ''): static
    {
        // #[Embed] put the request in the body before this method ran; the response shape is fixed
        // (additionalProperties: false), so it is consumed here rather than rendered.
        $source = $this->body['orderSource'] ?? null;
        $resolved = $source instanceof RequestInterface ? (string) (($source())->body['orderNo'] ?? '') : '';

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingComplete',
            'orderNo' => $resolved,
            'completeMessage' => '',
            'staticContent' => [
                'page' => 'shopping-complete',
                'title' => 'ご注文完了',
            ],
        ];

        return $this;
    }
}
