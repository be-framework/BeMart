---
layout: default
title: "/admin/order/edit"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/edit
EC-CUBE 受注登録 / 受注編集 — Order Tier-2 (`admin/Order/edit.twig`,
the ~1057-line multi-panel order editor).

GET /admin/order/edit            → blank "new order" editor
  GET /admin/order/edit?orderNo=…  → editor pre-filled for one order

Thin GET renderer. The sibling JSON resource {@see \MyVendor\BeMart\Resource\Page\Admin\Order}
carries the `goOrder` read + `doUpdateOrder` write; this resource is
the HTML editor shell only. An empty `$orderNo` renders the blank
editor (EC-CUBE's "受注登録" path — the render-smoke test exercises
this with empty JSON-backed fake storage); a known orderNo pre-fills; an unknown
orderNo is 404.

AUTHZ: the blank-editor path checks the admin session directly
(Pattern B — no Be transition is invoked when there is no order to
read); the pre-fill path delegates to {@see \AdminOrderFetched}, which
raises {@see \UnauthorizedAdminAccessException} for a non-admin
firewall. Both surface 403.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Optional |  |  |


### Response

_Not available_