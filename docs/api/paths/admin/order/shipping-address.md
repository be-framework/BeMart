---
layout: default
title: "/admin/order/shipping-address"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/shipping-address
EC-CUBE doSelectShippingAddress + doUpdateShippingAddress (admin
side) — 受注の配送先を操作する (Wave 9η).

POST /admin/order/shipping-address → doSelectShippingAddress
  PUT  /admin/order/shipping-address → doUpdateShippingAddress

Why a single resource for both transitions: they target the same
underlying state (the order's shipping-address row). POST means
"pick from the address book" (lookup by addressId, copy fields);
PUT means "overwrite the row with explicit fields". The collapse
mirrors the Wave 6R address-book resource which carries POST /
GET / PUT / DELETE on the same shape.

Note on actor scope: ALPS marks the two transitions `actor-customer`
(checkout flow). The Wave 9η iteration adds an admin-side entry
point because the back-office order-edit screen needs to manage the
shipping target after the order is finalized. The customer-side
renderers (Wave 3H static forms) still exist at
`page://self/shopping/shipping{,-edit}`.

Failure mapping (both methods):
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400
  - UnauthorizedAdminAccessException      → 403
  - OrderNotFoundException                → 404
  - AddressNotFoundException (POST only)  → 404




## GET
EC-CUBE 出荷登録 / 配送先編集 — Order Tier-2.

Thin GET renderer for `admin/Order/shipping.twig` (~709 lines).
The POST / PUT methods below carry the address-book-pick and the
explicit-overwrite transitions; this GET serves the editor shell
keyed by the order being shipped. BeMart has no Be transition to
READ an order's current shipping target, so the editor renders a
blank shipping form — the render-smoke test exercises this with
empty JSON-backed fake storage. AUTHZ is a direct admin-session check
(Pattern B — no Be transition is invoked on the GET path).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Optional |  |  |


### Response

_Not available_
## POST
doSelectShippingAddress — pick an address-book row for the order.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |
| addressId | string | 配送先住所ID |  | Required |  |  |


### Response

_Not available_
## PUT
doUpdateShippingAddress — overwrite the order's shipping fields.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |
| name01 | string | 姓 |  | Required |  |  |
| name02 | string | 名 |  | Required |  |  |
| postalCode | string | 郵便番号 |  | Required |  |  |
| pref | int | 都道府県 |  | Required |  |  |
| addr01 | string | 市区町村 |  | Required |  |  |
| addr02 | string | 番地・建物名 |  | Required |  |  |
| phoneNumber | string | 電話番号 |  | Required |  |  |


### Response

_Not available_