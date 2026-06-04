---
layout: default
title: "/admin/order"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order
EC-CUBE goOrder / doUpdateOrder — 受注詳細 (Wave 7).

- GET → goOrder        (read header + items + customer summary)
  - PUT → doUpdateOrder  (partial-update: discount / charge / usePoint)

The status-flip flow (doUpdateOrderStatus) lives at a sibling resource
`/admin/order-status` ({@see \OrderStatus}) — it is a sub-resource of
the order with workflow-significant semantics, so we keep its URL
distinct rather than overloading PUT here. Choice (B) from the Wave 7
design note.

Admin-only — both methods raise {@see \UnauthorizedAdminAccessException}
via the Be Final when the admin firewall is unset. CSRF is enforced
on PUT only (read-only GET does not need a token).

Failure mapping (cross-firewall AUTHZ → existence ladder):
  - Invalid CSRF (PUT)                    → 403
  - SemanticVariableException             → 400 (input format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404 (unknown orderNo)

The 403-before-404 ordering matches the Be Final's check sequence —
an admin-anonymous client learns NOTHING about which orderNos resolve.

Mass-assignment safety (PUT): see {@see \AdminUpdateOrderInput} — only
discount / charge / usePoint are editable. `orderNo` IS in the body
because it is the target selector (admin needs to pick which order),
but `customerId` / `total` / `orderStatus` are NOT writable from
here.




## GET
Wave 7: orderNo comes from the admin UI (click on an order-list
row, or pasted into the URL).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |


### Response

_Not available_
## PUT
Wave 7: every editable field is admin-form input. The orderNo
selector is also admin-controlled. Same taint discipline as the
Wave 5 / Wave 6 admin resources.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |
| discount | int | 値引き額 |  | Optional |  |  |
| charge | int | 手数料 |  | Optional |  |  |
| usePoint | int | 使用ポイント |  | Optional |  |  |


### Response

_Not available_