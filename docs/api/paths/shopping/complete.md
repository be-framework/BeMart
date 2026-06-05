---
layout: default
title: "/shopping/complete"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/complete
EC-CUBE goShoppingComplete — ご注文完了 (Phase 3 — thin renderer).

EC-CUBE renders the order-complete screen (ALPS `#ShoppingComplete`)
after `doCheckout` succeeds. BeMart's `Shopping/Checkout::onPost`
(doCheckout) returns the `CheckoutCompleted` projection and sets
`Location: /shopping/complete?orderNo=...`; this resource backs that
URL and renders `Shopping/complete.twig`.

Phase 3 enrichment — the complete screen displays the freshly-placed
order's number (`#orderNo`) and the per-order complete message
(`#completeMessage`), the two data descriptors ALPS `#ShoppingComplete`
carries beyond the `goTop` / `goCart` transitions. EC-CUBE re-fetches
the `Order` row by id from the request; BeMart mirrors that: the
post-checkout redirect carries `orderNo` as a query parameter, and the
resource resolves the finalized-order header through
{@see \OrderQueryInterface::byOrderNo} (the same NEW(1)-onwards row
`CheckoutCompleted` registered). The body then carries `orderNo` so
the screen shows the real order number.

`completeMessage` is intentionally empty — EC-CUBE lets payment
plugins append to it via `appendCompleteMessage()`, but the finalized
order header carries no such field ({@see \CheckoutCompleted} produces
an empty string in Pilot 5 — a future Plugin Pilot wires it up). The
body surfaces it as a `''` default so the template's
complete-message block degrades to empty, matching EC-CUBE's
plugin-less render.

No Be Framework chain — the screen is a pure read of an
already-finalized order, no domain transition. An unknown `orderNo`
(or none supplied — a direct visit to the URL) still renders the
thank-you screen; the order-number block simply stays empty.

Maps to `page://self/shopping/complete`.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Optional |  |  |


### Response

_Not available_