---
layout: default
title: "/cart/item"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /cart/item
EC-CUBE doAddCartItem —カートに商品を追加。

Resource is the HTTP entry point: it builds AddCartItemInput, hands it
to Becoming, and projects the resulting CartItemAdded into the response
body. Domain exceptions are mapped to HTTP codes per the integration
contract (see application-implement.md §DomainException → Code mapping).




## POST
Phase B Slice 9: all three params arrive from the HTTP request body
and are user-controlled. Declared as taint sources so Psalm can
trace them through Becoming into any downstream sink (Phase 2 will
surface real flows once Fake Reasons are swapped for DB-backed
implementations).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |
| quantity | int | 数量 |  | Optional |  |  |
| sessionPrefix | string |  | session-prefix-1 | Optional |  |  |
| operation | string |  |  | Optional |  |  |


### Response

_Not available_
## PUT
EC-CUBE doUpdateCartItemQuantity — replace an item's quantity
(Pilot 10). Idempotent (PUT semantics), CSRF-guarded.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |
| quantity | int | 数量 |  | Required |  |  |
| sessionPrefix | string |  | session-prefix-1 | Optional |  |  |


### Response

_Not available_
## DELETE
EC-CUBE doRemoveCartItem — remove an item from the cart (Pilot 11).

Idempotent (DELETE), CSRF-guarded.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| productCode | string | 商品コード |  | Required |  |  |
| sessionPrefix | string |  | session-prefix-1 | Optional |  |  |


### Response

_Not available_