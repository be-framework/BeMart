---
layout: default
title: "/admin/order-status"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order-status
EC-CUBE doUpdateOrderStatus — 受注ステータス変更 (Wave 7).

POST → flip the persisted orderStatus column on one order.

Status-flip is a sub-resource of the order rather than a method on
{@see \Order} because its semantics are workflow-significant (the
change has cascade effects in EC-CUBE — cancel reverses stock /
points, ship awards points, etc. — which the Phase 2 PurchaseFlow
adapter will wire up). Surfacing a distinct URL (`/admin/order-status`)
keeps the audit story explicit and matches the ALPS-level separation
of `doUpdateOrder` vs `doUpdateOrderStatus`.

Choice of POST (not PATCH): BEAR.Sunday's natural verb set is GET /
POST / PUT / DELETE — PATCH is not first-class. POST against this
sub-resource carries the same shape as Wave 6's DeleteCustomer
(POST + CSRF + target id in body).

Failure mapping:
  - Invalid CSRF                          → 403
  - SemanticVariableException             → 400 (orderStatus format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - OrderNotFoundException                → 404 (unknown orderNo)

Idempotency: when the supplied `orderStatus` matches the persisted
value, the projection carries `changed=false` and the storage is
untouched. A replay returns 200 with the same body shape — mirrors
AdminCustomerDeleted's `alreadyDeleted` discipline (Wave 6).

Mass-assignment safety: only `orderNo` (target) and `orderStatus`
(new value) are accepted; no path here reaches the other dtb_order
columns.




## GET
EC-CUBE 受注対応状況設定 — Setting/Shop Tier-2.

Thin GET renderer for `Setting/Shop/order_status.twig`. BeMart
has a per-order status-change transition on POST, but not yet a
master-data transition for editing status labels/colors.



### Request

_No parameters required_

### Response

_Not available_
## PUT
EC-CUBE doUpdateOrderStatusList — settings-side status list update.

This is intentionally separate from {@see \onPost()}, which
updates one order's workflow status. The settings screen submits
the master-list shape; this wave exposes a concrete CSRF/AUTHZ
surface and returns the accepted payload count without claiming
full EC-CUBE master-data persistence yet.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderStatuses | array |  | array () | Optional |  |  |
| orderStatusRows | string |  |  | Optional |  |  |


### Response

_Not available_
## POST
Wave 7: both `orderNo` and `orderStatus` are admin-form input
(orderNo selected from the order-list row, orderStatus picked
from a dropdown of dtb_order_status values).



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |
| orderStatus | int | 受注ステータス |  | Required |  |  |


### Response

_Not available_