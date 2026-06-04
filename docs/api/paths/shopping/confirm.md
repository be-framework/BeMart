<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/confirm
EC-CUBE goShoppingConfirm — 注文内容のご確認.

The order-review screen the customer confirms before `doCheckout`.
EC-CUBE's checkout flow runs `doConfirmOrder` → `ShoppingConfirm`
(ALPS `#ShoppingConfirm`) between `goShopping` and `doCheckout`.

Phase 3 enrichment — this resource now drives the `doConfirmOrder` Be
Becoming chain ({@see \ConfirmOrderInput} → … → {@see \OrderConfirmed})
rather than being a thin pure renderer. The chain resolves the
processing pre-order, runs the PurchaseFlow totals, verifies payment
and branches; on success the body carries the full confirm-screen
projection EC-CUBE's `Shopping/confirm.twig` renders — the customer
info, the order's line items, the payment method and the
tax-inclusive totals.

On a verify failure the chain produces an {@see \OrderConfirmFailed}
Final; the resource forwards the customer to the ShoppingError state
(`goShoppingError`), mirroring EC-CUBE's controller behaviour.

Maps to `page://self/shopping/confirm`. The submit target is
doCheckout (`page://self/shopping/checkout`).




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| preOrderId | string | 仮注文ID | aceface0000000000000000000000000000a11ce | Optional |  |  |
| paymentMethodId | int |  | 2 | Optional |  |  |


### Response

_Not available_