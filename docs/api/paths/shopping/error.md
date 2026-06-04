---
layout: default
title: "/shopping/error"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/error
EC-CUBE goShoppingError — 購入エラー表示 (Wave 3H pure renderer).

Pure static page: no Be Framework, no domain logic, no Reasons.
Anonymous-or-authenticated (the checkout flow lands here regardless
of the originating identity). Maps to `page://self/shopping/error`.

In production this page is hit by redirect from doConfirmOrder /
doCheckout when stock / payment / session checks fail. Wave 3H
renders the surface only — the actual error reason is not threaded
through here yet (production EC-CUBE puts the message in a flashbag).

The ALPS `#ShoppingError` resource declares a single outbound
transition: goCart.




## GET


### Request

_No parameters required_

### Response

_Not available_