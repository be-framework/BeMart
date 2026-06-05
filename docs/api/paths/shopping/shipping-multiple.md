---
layout: default
title: "/shopping/shipping-multiple"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/shipping-multiple
EC-CUBE goShoppingShippingMultiple — 複数配送先設定画面 (Wave 3H pure renderer).

Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
Maps to `page://self/shopping/shipping/multiple`.

Production EC-CUBE distributes cart items across multiple shipping
addresses (per-item address selection). Wave 3H exposes the shape
only; the cart-item × address allocation form is left as TODO
(the rt is `#Shopping` per ALPS, so on submit the flow returns to
the main shopping screen).




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST
EC-CUBE doSelectShippingAddress — accept the multi-shipping allocation.

The current page exposes the allocation form shape but has no cart-item
rows yet. The POST endpoint is still concrete: CSRF is enforced, the
transition is acknowledged, and the user returns to the shopping page.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| allocations | array |  | array () | Optional |  |  |


### Response

_Not available_